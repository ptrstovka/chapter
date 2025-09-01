<?php

namespace App\Support;

use App\Models\TemporaryUpload;
use Illuminate\Support\Collection;

interface FileListAdapter
{
    /**
     * Get the list of existing files.
     *
     * @return \Illuminate\Support\Collection<int, \App\Support\FileListItem>
     */
    public function list(): Collection;

    /**
     * Create a new file.
     */
    public function create(TemporaryUpload $upload): void;

    /**
     * Update an existing file.
     */
    public function update(FileListItem $file): void;

    /**
     * Delete an existing file.
     */
    public function delete(FileListItem $file): void;
}
