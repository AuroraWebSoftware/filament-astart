# AAuth VSCode Copilot Instructions

AAuth is a Laravel Auth package that combines Organization Based (OrBAC), Attribute Based (ABAC), and Role-Permission (RBAC) authentication methods with limitless hierarchical organization levels and attribute conditions.

## Core Library Knowledge

AAuth package offers these key features:
- Organization Based Access Control (OrBAC) and Row Level Data Filtering for Eloquent Models
- Attribute Based Access Control (ABAC) Eloquent Models
- User-Role Based Access Control (RBAC)
- Lean & Non-Complex Architecture
- DB Row Level Filtering for Eloquent Models (using OrBAC and ABAC)
- Built-in Blade Directives for permission control inside **Blade** files

## Key Terminology

### Organization
A hierarchical arrangement of eloquent models in a sequential tree. It consists of a central root organization node and sub-organization nodes connected via edges.

### Organization Scope
Each node in the organization tree has an organization scope with a level property that determines the position of the organization node in the tree.

### Organization Node
Each node in the organization tree represents an organization node, which is an Eloquent Model that can have a polymorphic relationship with another Eloquent Model.

### Permission
There are two types of permissions:
1. **System Permissions**: Plain permissions not related to the organization (backup_db, edit_website_logo, etc.)
2. **Organization Permissions**: Hierarchically controllable permissions

### User Role
Users can have multiple roles. There are two types of roles:
1. **System Role**: Plain roles for users not related to organizations (system admin, super admin, etc.)
2. **Organization Role**: Hierarchical position of a User in the Organization Tree (departmant maneger, school teacher, etc.)

## Classes, Traits, Interfaces & Functions - Usage Guide

### 1. User Configuration
**DO USE**: 
- `AAuthUser` trait and `AAuthUserContract` interface for User models when implementing AAuth
- Apply to your main User model to enable AAuth's permission system

**DON'T USE**: 
- Don't apply these to non-User models
- Don't apply to secondary user models that won't use AAuth permissions

```php
use Illuminate\Foundation\Auth\User as Authenticatable;
use AuroraWebSoftware\AAuth\Traits\AAuthUser;
use AuroraWebSoftware\AAuth\Contracts\AAuthUserContract;

class User extends Authenticatable implements AAuthUserContract
{
    use AAuthUser;
    
    // Your User model properties and methods
}
```

### 2. OrganizationService
**DO USE**:
- For all organization-related operations (creating, updating, or managing organization structures)
- Via dependency injection in controllers/services for better testability

**DON'T USE**:
- For permission checks (use AAuth facade instead)
- For role management (use RolePermissionService instead)

```php

// Alternative direct instantiation
$organizationService = new OrganizationService();

// Create organization scope
$scopeData = [
    'name' => 'Global Company Scope',
    'level' => 1,
    'status' => 'active',
];
$scope = $organizationService->createOrganizationScope($scopeData);

// Create organization node
$nodeData = [
    'name' => 'Headquarters',
    'organization_scope_id' => $scope->id,
    'parent_id' => null, // Root node
];
$node = $organizationService->createOrganizationNode($nodeData);

// Create child node
$branchData = [
    'name' => 'Eastern Branch',
    'organization_scope_id' => $scope->id,
    'parent_id' => $node->id,
];
$branchNode = $organizationService->createOrganizationNode($branchData);

// Link model with organization node
$department = Department::find(1);
$organizationService->createOrganizationNodeableRelation($department, $node->id);
```

### 3. RolePermissionService
**DO USE**:
- For all role and permission operations
- For attaching/detaching roles to users
- For managing permissions assigned to roles

**DON'T USE**:
- For direct permission checks (use AAuth facade instead)
- For organization structure management

```php

// Alternative direct instantiation
$rolePermissionService = new RolePermissionService();

// Create system role
$systemRoleData = [
    'organization_scope_id' => $scope->id,
    'type' => 'system',
    'name' => 'System Administrator',
    'status' => 'active',
];
$systemRole = $rolePermissionService->createRole($systemRoleData);

// Create organization role
$orgRoleData = [
    'organization_scope_id' => $scope->id,
    'type' => 'organization',
    'name' => 'Department Manager',
    'status' => 'active',
];
$orgRole = $rolePermissionService->createRole($orgRoleData);

// Attach system role to user
$rolePermissionService->attachSystemRoleToUser($systemRole->id, $user->id);

// Attach organization role to user
$rolePermissionService->attachOrganizationRoleToUser($node->id, $orgRole->id, $user->id);

// Add permission to role
$rolePermissionService->attachPermissionToRole('edit_organization_settings', $orgRole->id);

// Sync multiple permissions to role
$permissions = [
    'edit_organization_settings',
    'view_reports',
    'manage_users',
];
$rolePermissionService->syncPermissionsOfRole($permissions, $orgRole->id);

// Detach permission from role
$rolePermissionService->detachPermissionFromRole('manage_users', $orgRole->id);

// Detach role from user
$rolePermissionService->detachSystemRoleFromUser($systemRole->id, $user->id);
$rolePermissionService->detachOrganizationRoleFromUser($node->id, $orgRole->id, $user->id);
```

### 4. AAuth Interface and Traits for Models
**DO USE**:
- `AAuthOrganizationNodeInterface` and `AAuthOrganizationNode` trait for models that represent organization nodes
- `AAuthOrganizationNodeable` trait for models that need to be linked to organization nodes
- `AAuthAttributeRuleInterface` and `AAuthAttributeRule` trait for models that need ABAC rules

**DON'T USE**:
- On models that don't need organization-based access control
- Multiple AAuth traits on the same model unless specifically designed for it

```php

use AuroraWebSoftware\AAuth\Enums\ABACCondition;
use AuroraWebSoftware\AAuth\Interfaces\AAuthABACModelInterface;
use AuroraWebSoftware\AAuth\Interfaces\AAuthOrganizationNodeInterface;
use AuroraWebSoftware\AAuth\Traits\AAuthABACModel;
use AuroraWebSoftware\AAuth\Traits\AAuthOrganizationNode;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $name
 */
class OrganizationNodeable extends Model implements AAuthOrganizationNodeInterface, AAuthABACModelInterface
{
    use AAuthOrganizationNode;
    use AAuthABACModel;

    protected $fillable = ['name', 'age'];

    public static function getModelType(): string
    {
        return 'AuroraWebSoftware\AAuth\Tests\Models\OrganizationNodeable';
    }

    public function getModelId(): int
    {
        return $this->id;
    }

    public function getModelName(): ?string
    {
        return $this->name;
    }

    public static function getABACRules(): array
    {
        return [
            'name' => [ABACCondition::equal, ABACCondition::like],
            'age' => [ABACCondition::equal, ABACCondition::greater_then],
            'id' => [ABACCondition::equal, ABACCondition::greater_than_or_equal_to],
        ];
    }
}


// Model as an Organization Node
namespace App\Models;

use AuroraWebSoftware\AAuth\Interfaces\AAuthOrganizationNodeInterface;
use AuroraWebSoftware\AAuth\Traits\AAuthOrganizationNode;
use Illuminate\Database\Eloquent\Model;

class Department extends Model implements AAuthOrganizationNodeInterface
{
    use AAuthOrganizationNode;
    
    protected $fillable = ['name', 'description'];
    
        public static function getModelType(): string
    {
        return 'FQCN of Department';
    }

    public function getModelId(): int
    {
        return $this->id;
    }

    public function getModelName(): ?string
    {
        return $this->name;
    }

    // When using AAuthOrganizationNode, the model automatically has access to:
    // 1. Global scope to filter by permission
    // 2. Relationships to organization nodes
    // 3. Static methods for creating with organization nodes
}

// Model that needs to be linked to Organization Nodes
namespace App\Models;

use AuroraWebSoftware\AAuth\Interfaces\AAuthOrganizationNodeableInterface;
use AuroraWebSoftware\AAuth\Traits\AAuthOrganizationNodeable;
use Illuminate\Database\Eloquent\Model;

class Project extends Model implements AAuthOrganizationNodeableInterface
{
    use AAuthOrganizationNodeable;
    
    protected $fillable = ['name', 'budget', 'deadline'];

            public static function getModelType(): string
    {
        return 'FQCN of Project';
    }

    public function getModelId(): int
    {
        return $this->id;
    }

    public function getModelName(): ?string
    {
        return $this->name;
    }
    
    // Now this model can be linked to organization nodes
    // and will be filterable based on user's permissions
}

// Model with ABAC rules
namespace App\Models;

use AuroraWebSoftware\AAuth\Interfaces\AAuthAttributeRuleInterface;
use AuroraWebSoftware\AAuth\Traits\AAuthAttributeRule;
use Illuminate\Database\Eloquent\Model;

class Document extends Model implements implements AAuthABACModelInterface
{
    use AAuthABACModel;
    
    protected $fillable = ['title', 'content', 'status', 'department_id'];


    public static function getABACRules(): array
    {
        return [
            'title' => [ABACCondition::equal, ABACCondition::like],
            'attribute1' => [ABACCondition::equal, ABACCondition::greater_then],
            'attribute2' => [ABACCondition::equal, ABACCondition::greater_than_or_equal_to],
        ];
    }
}

```

### 5. AAuth Facade and Service Methods
**DO USE**:
- For permission checks in controllers, middleware, Blade files
- For getting organization nodes accessible to the current user
- For checking relationships between nodes

**DON'T USE**:
- For creating/managing organization structures (use OrganizationService)
- For role/permission assignments (use RolePermissionService)

```php
// Basic permission check
if (AAuth::can('edit_documents')) {
    // User has permission to edit documents
}

// Permission check with abort if not allowed
AAuth::passOrAbort('delete_documents');

// Get all permissions for the current role
$permissions = AAuth::permissions();

// Get all permitted organization nodes
$allNodes = AAuth::organizationNodes();
$withoutRoot = AAuth::organizationNodes(false);
$onlyDepartments = AAuth::organizationNodes(true, 'App\\Models\\Department');

// Check if node is descendant of another node
$isSubNode = AAuth::descendant($parentNodeId, $childNodeId);

// In controller
public function update(Request $request, Document $document)
{
    AAuth::passOrAbort('edit_documents');
    
    // Update document
    $document->update($request->validated());
    
    return redirect()->back()->with('success', 'Document updated!');
}

// In middleware
public class CheckPermission
{
    public function handle($request, $next, $permission)
    {
        if (!AAuth::can($permission)) {
            abort(403, 'Unauthorized');
        }
        
        return $next($request);
    }
}

// Route with middleware
Route::put('/documents/{document}', [DocumentController::class, 'update'])
    ->middleware('permission:edit_documents');
```

### 6. Blade Directives
**DO USE**:
- In Blade views for permission-based UI elements
- To conditionally show/hide parts of the UI

**DON'T USE**:
- For complex permission logic (move to controller)
- For non-permission related visibility

```blade
{{-- Check single permission --}}
@aauth('edit_documents')
    <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary">
        Edit Document
    </a>
@endaauth
```

**DO USE**:
- AAuth in Livewire components for permission checks
- Call organizational methods from Livewire components

**DON'T USE**:
- Create organization structures directly in Livewire components
- Expose sensitive permission logic in frontend code

```php
namespace App\Http\Livewire;

use Livewire\Component;
use AuroraWebSoftware\AAuth\Facades\AAuth;
use App\Models\Document;

class DocumentManager extends Component
{
    public $documents = [];
    public $canCreate = false;
    public $canEdit = false;
    public $canDelete = false;
    
    public function mount()
    {
        // Check permissions
        $this->canCreate = AAuth::can('create_documents');
        $this->canEdit = AAuth::can('edit_documents');
        $this->canDelete = AAuth::can('delete_documents');
        
        // Get permitted documents
        $this->documents = Document::all(); // Already filtered by AAuth scope
    }
    
    public function deleteDocument($id)
    {
        if (!AAuth::can('delete_documents')) {
            $this->addError('permission', 'You do not have permission to delete documents');
            return;
        }
        
        Document::find($id)->delete();
        $this->documents = Document::all();
    }
    
    public function render()
    {
        return view('livewire.document-manager');
    }
}
```



**DO USE**:
- Proper config file structure for permissions
- Published migrations and seeders
- Session based role selection

**DON'T USE**:
- Hardcoded permissions in the code
- Multiple role sessions

**DO USE**:
- For complex organizational hierarchies
- When users need access based on their position in the organization
- For data segregation between organization units

**DON'T USE**:
- For flat organization structures
- When simple role-based access is sufficient

```php
// Complex organizational hierarchy setup
public function createOrganizationalStructure(
    OrganizationService $organizationService,
    RolePermissionService $rolePermissionService
) {
    // Create organization scopes
    $globalScope = $organizationService->createOrganizationScope([
        'name' => 'Global Company',
        'level' => 1,
        'status' => 'active',
    ]);
    
    $regionScope = $organizationService->createOrganizationScope([
        'name' => 'Regional Offices',
        'level' => 2,
        'status' => 'active',
    ]);
    
    $branchScope = $organizationService->createOrganizationScope([
        'name' => 'Branch Offices',
        'level' => 3,
        'status' => 'active',
    ]);
    
    $departmentScope = $organizationService->createOrganizationScope([
        'name' => 'Departments',
        'level' => 4,
        'status' => 'active',
    ]);
    
    // Create root organization node
    $globalNode = $organizationService->createOrganizationNode([
        'name' => 'Global Headquarters',
        'organization_scope_id' => $globalScope->id,
        'parent_id' => null,
    ]);
    
    // Create regions
    $europeNode = $organizationService->createOrganizationNode([
        'name' => 'Europe',
        'organization_scope_id' => $regionScope->id,
        'parent_id' => $globalNode->id,
    ]);
    
    $americaNode = $organizationService->createOrganizationNode([
        'name' => 'Americas',
        'organization_scope_id' => $regionScope->id,
        'parent_id' => $globalNode->id,
    ]);
    
    // Create branches
    $ukNode = $organizationService->createOrganizationNode([
        'name' => 'United Kingdom',
        'organization_scope_id' => $branchScope->id,
        'parent_id' => $europeNode->id,
    ]);
    
    $germanyNode = $organizationService->createOrganizationNode([
        'name' => 'Germany',
        'organization_scope_id' => $branchScope->id,
        'parent_id' => $europeNode->id,
    ]);
    
    $usNode = $organizationService->createOrganizationNode([
        'name' => 'United States',
        'organization_scope_id' => $branchScope->id,
        'parent_id' => $americaNode->id,
    ]);
    
    // Create departments
    $ukSalesNode = $organizationService->createOrganizationNode([
        'name' => 'UK Sales',
        'organization_scope_id' => $departmentScope->id,
        'parent_id' => $ukNode->id,
    ]);
    
    $ukMarketingNode = $organizationService->createOrganizationNode([
        'name' => 'UK Marketing',
        'organization_scope_id' => $departmentScope->id,
        'parent_id' => $ukNode->id,
    ]);
    
    // Create roles
    $globalManagerRole = $rolePermissionService->createRole([
        'name' => 'Global Manager',
        'type' => 'organization',
        'organization_scope_id' => $globalScope->id,
        'status' => 'active',
    ]);
    
    $regionalManagerRole = $rolePermissionService->createRole([
        'name' => 'Regional Manager',
        'type' => 'organization',
        'organization_scope_id' => $regionScope->id,
        'status' => 'active',
    ]);
    
    $departmentManagerRole = $rolePermissionService->createRole([
        'name' => 'Department Manager',
        'type' => 'organization',
        'organization_scope_id' => $departmentScope->id,
        'status' => 'active',
    ]);
    
    // Assign permissions to roles
    $rolePermissionService->attachPermissionToRole('view_all_reports', $globalManagerRole->id);
    $rolePermissionService->attachPermissionToRole('manage_organizational_structure', $globalManagerRole->id);
    
    $rolePermissionService->attachPermissionToRole('view_regional_reports', $regionalManagerRole->id);
    $rolePermissionService->attachPermissionToRole('manage_regional_budgets', $regionalManagerRole->id);
    
    $rolePermissionService->attachPermissionToRole('manage_department_staff', $departmentManagerRole->id);
    $rolePermissionService->attachPermissionToRole('view_department_reports', $departmentManagerRole->id);
    
    // Assign roles to users
    $ceo = User::where('email', 'ceo@example.com')->first();
    $europeHead = User::where('email', 'europe_head@example.com')->first();
    $ukSalesManager = User::where('email', 'uk_sales@example.com')->first();
    
    $rolePermissionService->attachOrganizationRoleToUser($globalNode->id, $globalManagerRole->id, $ceo->id);
    $rolePermissionService->attachOrganizationRoleToUser($europeNode->id, $regionalManagerRole->id, $europeHead->id);
    $rolePermissionService->attachOrganizationRoleToUser($ukSalesNode->id, $departmentManagerRole->id, $ukSalesManager->id);
    
    return "Organizational structure created successfully!";
}

// Usage in controller with AAuth filtering
public function salesReports()
{
    // This will only return reports accessible to the current user based on their position
    // in the organization hierarchy
    $reports = SalesReport::all();
    
    return view('reports.sales', compact('reports'));
}
```

## Code Completion Strategies

When working with AAuth, consider these best practices:

1. Always properly define AAuth traits and interfaces on models
2. Use dependency injection for OrganizationService and RolePermissionService
3. Use the AAuth facade for permission checks
4. Structure your organization hierarchy thoughtfully
5. Use ABAC rules for complex permission logic
6. Combine OrBAC and ABAC for powerful access control
7. Set session-based roles for proper user context
8. Use proper blade directives for permission-based UI elements
9. Leverage Livewire and Filament integration for modern UIs
10. use english phrases for model names

Following these instructions will help you effectively use AAuth in your Laravel projects with best practices.