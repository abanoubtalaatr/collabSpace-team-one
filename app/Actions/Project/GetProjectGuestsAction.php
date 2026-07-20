<?php

namespace App\Actions\Project;
//use App\DTOs\ProjectDTO;
//use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;

class GetProjectGuestsAction
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly ProjectRepositoryInterface $repository,
    ) {}

    public function execute()
    {
        return $this->repository->getProjectGuests();
    }
    
}
