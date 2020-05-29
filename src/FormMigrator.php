<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;

class FormMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\PreparesPathFolder;

    protected $form;
    protected $blueprint;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        $this
            ->setNewPath(resource_path("forms/{$this->handle}.yaml"))
            ->validateUnique()
            ->parseForm()
            ->migrateFieldsToBlueprint()
            ->migrateFormSchema()
            ->saveMigratedYaml($this->blueprint, $this->blueprintPath())
            ->saveMigratedYaml($this->form)
            ->migrateSubmissions();
    }

    /**
     * Specify unique paths that shouldn't be overwritten.
     *
     * @return array
     */
    protected function uniquePaths()
    {
        return [
            $this->newPath(),
            $this->blueprintPath(),
            $this->submissionsPath(),
        ];
    }

    /**
     * Parse user.
     *
     * @param string $relativePath
     * @return $this
     */
    protected function parseForm()
    {
        $this->form = $this->getSourceYaml("settings/formsets/{$this->handle}.yaml");

        return $this;
    }

    /**
     * Migrate default v2 form schema to default v3 schema.
     *
     * @return $this
     */
    protected function migrateFormSchema()
    {
        unset($this->form['fields']);
        unset($this->form['columns']);

        $this->form['blueprint'] = $this->migrateBlueprintHandle();

        return $this;
    }

    /**
     * Migrate form fields to blueprint schema.
     *
     * @return $this
     */
    protected function migrateFieldsToBlueprint()
    {
        $fields = collect($this->form['fields'])
            ->map(function ($field, $handle) {
                return [
                    'handle' => $handle,
                    'field' => $this->migrateField($field, $handle),
                ];
            })
            ->values()
            ->all();

        $this->blueprint = [
            'title' => $this->form['title'],
            'fields' => $fields,
        ];

        return $this;
    }

    /**
     * Migrate field.
     *
     * @param array $field
     * @param string $handle
     * @return array
     */
    protected function migrateField($field, $handle)
    {
        $field = array_merge(['type' => 'text'], $field);

        if (isset($this->form['columns']) && ! in_array($handle, $this->form['columns'])) {
            $field['listable'] = false;
        }

        return $field;
    }

    /**
     * Migrate blueprint handle.
     *
     * @return string
     */
    protected function migrateBlueprintHandle()
    {
        $suffix = Str::endsWith($this->handle, '_form')
            ? ''
            : '_form';

        return $this->handle.$suffix;
    }

    /**
     * Get blueprint path.
     *
     * @return string
     */
    protected function blueprintPath()
    {
        $handle = $this->migrateBlueprintHandle();

        return resource_path("blueprints/{$handle}.yaml");
    }

    /**
     * Migrate submissions.
     *
     * @return $this
     */
    protected function migrateSubmissions()
    {
        $this->prepareFolder($newPath = $this->submissionsPath());

        $this->files->copyDirectory($this->sitePath("storage/forms/{$this->handle}"), $newPath);

        return $this;
    }

    /**
     * Get submissions path.
     *
     * @return string
     */
    protected function submissionsPath()
    {
        return storage_path("forms/{$this->handle}");
    }
}
