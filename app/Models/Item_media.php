<?php

namespace App\Models;

use CodeIgniter\Model;

class Item_media extends Model
{
    protected $table      = 'item_media';
    protected $primaryKey = 'id';
    protected $allowedFields = ['item_id', 'media_type', 'filename', 'original_name', 'sort_order', 'created_at'];

    /**
     * Get all media entries for a given item.
     */
    public function get_by_item(int $item_id, ?string $media_type = null): array
    {
        $builder = $this->where('item_id', $item_id)->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC');
        if ($media_type !== null) {
            $builder = $builder->where('media_type', $media_type);
        }
        return $builder->findAll();
    }

    /**
     * Save a new media entry for an item.
     */
    public function save_media(int $item_id, string $media_type, string $filename, string $original_name = '', int $sort_order = 0): bool|int
    {
        return $this->insert([
            'item_id'       => $item_id,
            'media_type'    => $media_type,
            'filename'      => $filename,
            'original_name' => $original_name,
            'sort_order'    => $sort_order,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete a specific media entry by id.
     */
    public function delete_media(int $media_id): bool
    {
        return $this->delete($media_id);
    }

    /**
     * Delete all media entries for an item, optionally filtered by type.
     */
    public function delete_by_item(int $item_id, ?string $media_type = null): bool
    {
        $builder = $this->where('item_id', $item_id);
        if ($media_type !== null) {
            $builder = $builder->where('media_type', $media_type);
        }
        return $builder->delete();
    }
}
