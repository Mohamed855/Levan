<?php

namespace App\Data\Tenants\BusinessCenter\Company;

final class CompanyCreateDTO
{
    public function __construct(
        public string $name,
        public string $status = 'active',
        public ?string $logo_path = null,
        public ?float $target_percentage = null,
        /** @var AuthorizedPersonInput[] */
        public array $authorized_people = [],
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Build From Array
    |--------------------------------------------------------------------------
    | Create an instance from validated array input.
    */
    public static function fromArray(array $input): self
    {
        $people = [];
        if (isset($input['authorized_people']) && is_array($input['authorized_people'])) {
            foreach ($input['authorized_people'] as $item) {
                $person = AuthorizedPersonInput::fromArray((array)$item);
                if ($person->isMeaningful()) {
                    $people[] = $person;
                }
            }
        }

        return new self(
            name: trim((string)$input['name']),
            status: $input['status'] ?? 'active',
            logo_path: null,
            target_percentage: isset($input['target_percentage']) ? (float) $input['target_percentage'] : null,
            authorized_people: $people,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | To Model Array
    |--------------------------------------------------------------------------
    | Convert the DTO into an array suitable for Eloquent create().
    */
    public function toModelArray(): array
    {
        return [
            'name'      => $this->name,
            'status'    => $this->status,
            'logo_path' => $this->logo_path,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | With Logo Path
    |--------------------------------------------------------------------------
    */
    public function withLogoPath(string $path): self
    {
        $this->logo_path = $path;
        return $this;
    }
}