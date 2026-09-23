<?php

namespace App\Data\Tenants\BusinessCenter\Company;

final class CompanyUpdateDTO
{
    public ?string $name = null;
    public ?string $logo_path = null;
    public ?string $status = null;
    public ?float $target_percentage = null;
    /** @var AuthorizedPersonInput[]|null */
    public ?array $authorized_people = null;

    /*
    |--------------------------------------------------------------------------
    | Internal Provided Flags
    |--------------------------------------------------------------------------
    | Track which keys were actually provided to avoid unintended updates.
    */
    private array $provided = [];

    /*
    |--------------------------------------------------------------------------
    | Private Constructor
    |--------------------------------------------------------------------------
    | Use static constructor only.
    */
    private function __construct() {}

    /*
    |--------------------------------------------------------------------------
    | Build From Array
    |--------------------------------------------------------------------------
    | Create an instance from validated array input while tracking provided keys.
    */
    public static function fromArray(array $validated): self
    {
        $self = new self();
        $n = fn(?string $v) => isset($v) ? (trim($v) === '' ? null : trim($v)) : null;

        $set = function (string $key, $value) use ($self) {
            $self->provided[$key] = true;
            $self->$key = $value;
        };

        if (array_key_exists('name', $validated)) {
            $set('name', $n($validated['name']));
        }

        if (array_key_exists('status', $validated)) {
            $set('status', $n($validated['status']));
        }

        if (array_key_exists('logo_path', $validated)) {
            $self->provided['logo_path'] = true;
            $self->logo_path = $validated['logo_path'];
        }

        if (array_key_exists('target_percentage', $validated)) {
            $self->provided['target_percentage'] = true;
            $self->target_percentage = isset($validated['target_percentage']) ? (float) $validated['target_percentage'] : null;
        }

        if (array_key_exists('authorized_people', $validated)) {
            $self->provided['authorized_people'] = true;
            $people = [];
            foreach ((array)$validated['authorized_people'] as $item) {
                $person = AuthorizedPersonInput::fromArray((array)$item);
                if ($person->isMeaningful()) {
                    $people[] = $person;
                }
            }
            $self->authorized_people = $people;
        }

        return $self;
    }

    /*
    |--------------------------------------------------------------------------
    | Was Provided
    |--------------------------------------------------------------------------
    | Check if a specific key was provided in the request payload.
    */
    public function wasProvided(string $key): bool
    {
        return $this->provided[$key] ?? false;
    }

    /*
    |--------------------------------------------------------------------------
    | To Model Array
    |--------------------------------------------------------------------------
    | Convert only provided keys to an array suitable for Eloquent update().
    */
    public function toModelArray(): array
    {
        $out = [];
        foreach (['name', 'status'] as $key) {
            if ($this->wasProvided($key)) {
                $out[$key] = $this->$key;
            }
        }

        if ($this->wasProvided('logo_path')) {
            $out['logo_path'] = $this->logo_path;
        }

        return $out;
    }

    /*
    |--------------------------------------------------------------------------
    | With Logo Path
    |--------------------------------------------------------------------------
    */
    public function withLogoPath(string $path): self
    {
        $this->provided['logo_path'] = true;
        $this->logo_path = $path;
        return $this;
    }
}