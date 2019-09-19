<?php

namespace Statamic\Migrator\Migrators;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Statamic\Migrator\Exceptions\AlreadyExistsException;
use Statamic\Migrator\Exceptions\EmailRequiredException;

class UserMigrator extends Migrator
{
    protected $user;
    protected $newPath;

    /**
     * Migrate file.
     *
     * @param string $handle
     * @param bool $overwrite
     */
    public function migrate($handle, $overwrite = false)
    {
        $this->user = $this->getSourceYaml($handle);

        $this
            ->validateEmail()
            ->setNewPath()
            ->validateUnique($overwrite)
            ->migrateUserSchema()
            ->removeOldUser($handle)
            ->saveMigratedToYaml($this->newPath, $this->user);
    }

    /**
     * Validate email is present on user to be used as new handle.
     *
     * @return $this
     */
    protected function validateEmail()
    {
        if (! isset($this->user['email'])) {
            throw new EmailRequiredException;
        }

        return $this;
    }

    /**
     * Set new path to be used with new email handle.
     *
     * @return $this
     */
    protected function setNewPath()
    {
        $email = $this->user['email'];

        $this->newPath = base_path("users/{$email}.yaml");

        return $this;
    }

    /**
     * Validate unique.
     *
     * @param bool $overwrite
     * @return $this
     */
    protected function validateUnique($overwrite)
    {
        if (! $overwrite && $this->files->exists($this->newPath)) {
            throw new AlreadyExistsException;
        }

        return $this;
    }

    /**
     * Migrate default v2 user schema to default v3 user schema.
     *
     * @return $this
     */
    protected function migrateUserSchema()
    {
        $user = collect($this->user);

        if ($user->has('first_name') || $user->has('last_name')) {
            $user['name'] = $user->only('first_name', 'last_name')->filter()->implode(' ');
        }

        $this->user = $user->except('first_name', 'last_name', 'email')->all();

        return $this;
    }

    /**
     * Remove old user file.
     *
     * @param string $handle
     * @return $this
     */
    protected function removeOldUser($handle)
    {
        if ($this->files->exists($oldFileInNewPath = base_path("users/{$handle}.yaml"))) {
            $this->files->delete($oldFileInNewPath);
        }

        return $this;
    }
}
