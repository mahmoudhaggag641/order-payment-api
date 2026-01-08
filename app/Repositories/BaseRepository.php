<?php

namespace App\Repositories;

use App\Helpers\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseRepository
{
    public function __construct(public Model $model) {}

    public abstract function query($query, array $params = []);

    public abstract function transform($paginator);

    public abstract function formatParams(array $params, $model = null): array;

    public abstract function setRelations($model, array $params);

    public abstract function canDelete($model);


    public function findById($id)
    {
        $model = $this->model->query()->info()->where('id', $id)->first();

        if (!$model) {
            throw new HttpResponseException(ApiResponse::notFound());
        }

        return $model;
    }

    public function findByUuid($uuid)
    {
        $model = $this->model->query()->info()->where('uuid', $uuid)->first();

        if (!$model) {
            throw new HttpResponseException(ApiResponse::notFound());
        }

        return $model;
    }

    public function getData($params)
    {
        $sortBy = gv($params, 'sort_by', 'created_at');
        $sortOrder = gv($params, 'sort_order', 'desc');

        $query = $this->model->query()->summary();

        $this->query($query, $params);

        return $query->orderBy($sortBy, $sortOrder);
    }

    public function paginate($params)
    {
        $perPage = (int) gv($params, 'per_page', 10);

        return $this->transform($this->getData($params)->simplePaginate($perPage));
    }

    public function create($params)
    {
        $model = $this->model->forceCreate($this->formatParams($params));

        $this->setRelations($model, $params);

        return $model;
    }

    public function update($model, $params): bool
    {
        $updated = $model->forceFill($this->formatParams($params, $model))->save();

        $this->setRelations($model, $params);

        return $updated;
    }

    public function delete($model)
    {
        $this->canDelete($model);

        $model->delete();
    }
}
