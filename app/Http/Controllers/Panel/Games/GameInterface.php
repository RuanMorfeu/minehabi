<?php

namespace App\Http\Controllers\Panel\Games;

interface GameInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getDescription(): array;

    public function getIcon(): string;

    public function getBanner(): string;

    public function getPresellSettings(): array;

    public function getRealSettings(): array;
}
