<?php

namespace App\Adapters;

use App\Models\Lesson;
use App\Models\Resource;
use App\Models\TemporaryUpload;
use App\Support\FileListAdapter;
use App\Support\FileListItem;
use Illuminate\Support\Collection;

class LessonResourceFileListAdapter implements FileListAdapter
{
    public function __construct(
        protected Lesson $lesson
    ) {}

    public function list(): Collection
    {
        return $this->lesson->resources->map(fn (Resource $resource) => new FileListItem(
            id: $resource->id,
            url: $resource->getDirectUrl(),
            name: $resource->client_file_name,
            size: $resource->size,
            mime: $resource->mime_type,
            source: $resource,
        ));
    }

    public function create(TemporaryUpload $upload): void
    {
        $filePath = $upload->copyToContentDisk(Resource::dir());

        $resource = new Resource([
            'file_path' => $filePath,
            'client_file_name' => $upload->client_file_name,
            'size' => $upload->size,
            'mime_type' => $upload->mime_type,
        ]);
        $resource->course()->associate($this->lesson->course);
        $resource->save();

        $this->lesson->resources()->attach($resource);
    }

    public function update(FileListItem $file): void
    {
        if ($file->source instanceof Resource) {
            $file->source->update([
                'client_file_name' => $file->name,
            ]);
        }
    }

    public function delete(FileListItem $file): void
    {
        if ($file->source instanceof Resource) {
            $this->lesson->resources()->detach($file->source);

            $file->source->delete();
        }
    }
}
