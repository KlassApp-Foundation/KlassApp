<?php

namespace App\AiAgents\Concerns;

/**
 * Reusable pre-execution validation guard mechanism for Toshi write tools.
 *
 * Usage: add `use HasPreExecutionGuards;` and `implements HasPreExecutionGuards`
 * to the tool class. Define guard methods as `protected function guardX(): ?string`
 * returning an error message string on failure or null on pass.
 *
 * Call before confirmOrExecute():
 *   $error = $this->runGuards();
 *   if ($error) return $error;
 */
trait HasPreExecutionGuards
{
    /**
     * Run all guard methods on this tool. A guard method is any protected
     * method whose name starts with "guard". Returns the first error
     * message found, or null if all guards pass.
     */
    protected function runGuards(): ?string
    {
        $methods = (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PROTECTED);
        foreach ($methods as $method) {
            if (str_starts_with($method->name, 'guard')) {
                $result = $this->{$method->name}();
                if ($result !== null) {
                    return $result;
                }
            }
        }
        return null;
    }
}
