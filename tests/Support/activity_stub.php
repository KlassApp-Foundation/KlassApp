<?php

if (! function_exists('activity')) {
    function activity()
    {
        return new class
        {
            public function performedOn(mixed $model): self
            {
                return $this;
            }

            public function causedBy(mixed $user): self
            {
                return $this;
            }

            public function withProperties(mixed $properties): self
            {
                return $this;
            }

            public function useLog(mixed $logName): self
            {
                return $this;
            }

            public function log(mixed $message): mixed
            {
                return null;
            }
        };
    }
}
