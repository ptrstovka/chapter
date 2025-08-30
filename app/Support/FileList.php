<?php

namespace App\Support;

use App\Models\TemporaryUpload;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;

class FileList
{
    public function __construct(
        protected FileListAdapter $adapter
    ) {}

    /**
     * Get the file list adapter.
     */
    public function getAdapter(): FileListAdapter
    {
        return $this->adapter;
    }

    /**
     * Get all files in the list.
     *
     * @return \Illuminate\Support\Collection<int, \App\Support\FileListItem>
     */
    public function all(): Collection
    {
        return $this->adapter->list();
    }

    /**
     * Synchronize files from the request.
     */
    public function syncFromRequest(string $key): void
    {
        $files = Request::input($key);

        if (! is_array($files) || empty($files)) {
            $this->adapter->list()->each(fn (FileListItem $item) => $this->adapter->delete($item));

            return;
        }

        $files = collect($files);

        $toKeep = $files->where('type', 'existing')->keyBy('id');

        $this->adapter->list()->each(function (FileListItem $item) use ($toKeep) {
            if ($existing = $toKeep->get($item->id)) {
                $this->adapter->update(
                    new FileListItem(
                        id: $item->id,
                        url: $item->url,
                        name: Arr::get($existing, 'name', $item->name) ?: $item->name,
                        size: $item->size,
                        mime: $item->mime,
                        source: $item->source,
                    )
                );
            } else {
                $this->adapter->delete($item);
            }
        });

        $toCreate = $files->where('type', 'temporary')->pluck('id');
        if ($toCreate->isNotEmpty()) {
            TemporaryUpload::query()
                ->whereIn('uuid', $toCreate)
                ->get()
                ->each(function (TemporaryUpload $upload) {
                    $this->adapter->create($upload);

                    $upload->delete();
                });
        }
    }
}
