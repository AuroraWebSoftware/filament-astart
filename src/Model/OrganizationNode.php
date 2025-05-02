<?php

namespace AuroraWebSoftware\FilamentAstart\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationNode extends \AuroraWebSoftware\AAuth\Models\OrganizationNode
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationNode::class, 'parent_id');
    }
}
