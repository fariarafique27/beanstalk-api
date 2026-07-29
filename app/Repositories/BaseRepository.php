<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository 
{
    protected $model;


    public function all()
    {
        return $this->model->all();
    }
    // public function getOrgnizationId(){
    //     return auth()->user()->organization->id;
    // }
    public function find($id)
    {
        return $this->model->find($id);
    }

    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update($id, array $data)
    {
        $record = $this->find($id);
        if ($record) {
            $record->update($data);
            return $record;
        }
        return false;
    }

    public function delete($id)
    {
        $record = $this->find($id);
        if ($record) {
            return $record->delete();
        }
        return false;
    }

    public function where($column, $value)
    {
        return $this->model->where($column, $value);
    }

    public function paginate($perPage = 15)
    {
        return $this->model->paginate($perPage);
    }
}

?>
