<?php 

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    //new 
    
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        $static = self::resolveFilterClass();
        
        return (new $static($request))->apply($query);
    }

    public static function resolveFilterClass(): string
    {
        return 'App\\Filters\\'.class_basename(self::class).'Filter';
    }
}