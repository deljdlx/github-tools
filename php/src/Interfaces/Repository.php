<?php
namespace Deljdlx\Github\Interfaces;

interface Repository
{
    public function getName(): string;
    public function getFullName(): string;
    public function getSlug(): string;
    public function getUrl(): string;
    public function isArchived(): bool;
    public function isPrivate(): bool;
    public function getMainBranch(): string;
    public function getReadme(): string|false;
}