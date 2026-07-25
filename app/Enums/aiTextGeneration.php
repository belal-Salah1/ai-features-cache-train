<?php

namespace App;

enum aiTextGeneration
{
    case PROCESSING;
    case COMPLETED;
    case FAILED;

    public function toString(): string
    {
        return match($this) {
            self::PROCESSING => 'processing',
            self::COMPLETED => 'completed',
            self::FAILED => 'failed',
        };
    }
}
