<?php

namespace AuroraWebSoftware\FilamentAstart\Model;

use AuroraWebSoftware\AAuth\Models\OrganizationScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationNode extends \AuroraWebSoftware\AAuth\Models\OrganizationNode
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationNode::class, 'parent_id');
    }

    public function organizationScope(): BelongsTo
    {
        return $this->belongsTo(OrganizationScope::class);
    }

    public function children()
    {
        return $this->hasMany(OrganizationNode::class, 'parent_id');
    }
}
