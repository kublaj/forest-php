<?php

namespace ForestAdmin\Liana\Exception;


class FieldNotFoundException extends NotFoundException
{
    /**
     * FieldNotFoundException constructor.
     * @param string $name
     * @param array|null $existing
     */
    public function __construct($name, $existing = null)
    {
        $this->message = "Field not found: {$name}.";
        
        if(!is_null($existing)) {
            if(is_array($existing) && count($existing)) {
                $existing = join(', ', $existing);
            } else {
                $existing = "(none)";
            }
            
            $this->message .= " (existing: ".$existing.")";
        }
    }
}