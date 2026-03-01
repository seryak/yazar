<?php

declare(strict_types=1);

namespace App\PHPStan;

use Composer\InstalledVersions;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodRef;
use ShipMonk\PHPStan\DeadCode\Graph\ClassMethodUsage;
use ShipMonk\PHPStan\DeadCode\Graph\UsageOrigin;
use ShipMonk\PHPStan\DeadCode\Provider\MemberUsageProvider;
use ShipMonk\PHPStan\DeadCode\Provider\VirtualUsageData;

final class LaravelUsageProvider implements MemberUsageProvider
{
    private bool $enabled;

    private const SERVICE_PROVIDER_METHODS = ['register', 'boot'];

    private const ROUTE_HTTP_METHODS = [
        'get', 'post', 'put', 'patch', 'delete', 'options', 'any', 'match',
    ];

    private const ROUTE_CLASS_NAMES = [
        'Route',
        'Illuminate\\Support\\Facades\\Route',
    ];

    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return list<ClassMethodUsage>
     */
    public function getUsages(Node $node, Scope $scope): array
    {
        if (! $this->enabled || ! $this->isLaravelInstalled()) {
            return [];
        }

        return [
            ...$this->getRouteUsages($node, $scope),
            ...$this->getArtisanCommandUsages($node, $scope),
            ...$this->getServiceProviderUsages($node, $scope),
            ...$this->getEloquentAccessorUsages($node, $scope),
        ];
    }

    /**
     * Detects controller methods registered via Route::get/post/etc.
     *
     * Pattern: Route::get('/path', [Controller::class, 'method'])
     *
     * @return list<ClassMethodUsage>
     */
    private function getRouteUsages(Node $node, Scope $scope): array
    {
        if (! $node instanceof StaticCall) {
            return [];
        }

        if (! $node->class instanceof Name) {
            return [];
        }

        if (! in_array((string) $node->class, self::ROUTE_CLASS_NAMES, true)) {
            return [];
        }

        if (! $node->name instanceof Identifier) {
            return [];
        }

        if (! in_array(strtolower($node->name->toString()), self::ROUTE_HTTP_METHODS, true)) {
            return [];
        }

        $actionArg = $node->args[1] ?? null;
        if (! $actionArg instanceof Arg) {
            return [];
        }

        $action = $actionArg->value;

        // String syntax: 'App\Http\Controllers\Controller@method'
        if ($action instanceof String_ && str_contains($action->value, '@')) {
            [$controllerClass, $controllerMethod] = explode('@', $action->value, 2);

            return [
                new ClassMethodUsage(
                    UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Called via Laravel route')),
                    new ClassMethodRef($controllerClass, $controllerMethod, false),
                ),
            ];
        }

        // Array syntax: [Controller::class, 'method']
        if (! $action instanceof Array_ || count($action->items) !== 2) {
            return [];
        }

        $classItem = $action->items[0]->value ?? null;
        $methodItem = $action->items[1]->value ?? null;

        if (! $classItem instanceof ClassConstFetch || ! $classItem->class instanceof Name) {
            return [];
        }

        if (! $methodItem instanceof String_) {
            return [];
        }

        $controllerClass = $scope->resolveName($classItem->class);
        $controllerMethod = $methodItem->value;

        return [
            new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Called via Laravel route')),
                new ClassMethodRef($controllerClass, $controllerMethod, false),
            ),
        ];
    }

    /**
     * Marks handle() as used in classes extending Illuminate\Console\Command.
     *
     * @return list<ClassMethodUsage>
     */
    private function getArtisanCommandUsages(Node $node, Scope $scope): array
    {
        if (! $node instanceof ClassMethod || $node->name->toString() !== 'handle') {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null || ! $classReflection->isSubclassOf('Illuminate\\Console\\Command')) {
            return [];
        }

        return [
            new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Called via artisan CLI')),
                new ClassMethodRef($classReflection->getName(), 'handle', false),
            ),
        ];
    }

    /**
     * Marks register() and boot() as used in classes extending Illuminate\Support\ServiceProvider.
     *
     * @return list<ClassMethodUsage>
     */
    private function getServiceProviderUsages(Node $node, Scope $scope): array
    {
        if (! $node instanceof ClassMethod
            || ! in_array($node->name->toString(), self::SERVICE_PROVIDER_METHODS, true)) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null
            || ! $classReflection->isSubclassOf('Illuminate\\Support\\ServiceProvider')) {
            return [];
        }

        return [
            new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Called by Laravel service container')),
                new ClassMethodRef($classReflection->getName(), $node->name->toString(), false),
            ),
        ];
    }

    /**
     * Marks get*Attribute() methods as used in Eloquent models.
     *
     * Accessors are called via magic ($model->snake_case, $model->camelCase,
     * toArray(), etc.) which cannot be statically tracked.
     *
     * @return list<ClassMethodUsage>
     */
    private function getEloquentAccessorUsages(Node $node, Scope $scope): array
    {
        if (! $node instanceof ClassMethod) {
            return [];
        }

        $name = $node->name->toString();
        if (! str_starts_with($name, 'get') || ! str_ends_with($name, 'Attribute')) {
            return [];
        }

        $classReflection = $scope->getClassReflection();
        if ($classReflection === null
            || ! $classReflection->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
            return [];
        }

        return [
            new ClassMethodUsage(
                UsageOrigin::createVirtual($this, VirtualUsageData::withNote('Eloquent accessor')),
                new ClassMethodRef($classReflection->getName(), $name, false),
            ),
        ];
    }

    private function isLaravelInstalled(): bool
    {
        foreach (InstalledVersions::getInstalledPackages() as $package) {
            if ($package === 'laravel/framework') {
                return true;
            }
        }

        return false;
    }
}
