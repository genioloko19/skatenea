<?php

declare(strict_types=1);

namespace Skatenea\Application;

use Skatenea\Core\HttpException;
use Skatenea\Domain\User\User;
use Skatenea\Infrastructure\Persistence\PreferencesRepositoryInterface;

final class PreferencesService
{
    private const THEMES = [
        'aurora', 'neon', 'sunset', 'ocean', 'forest', 'candy',
        'cyber', 'royal', 'terracotta', 'mono', 'tropical', 'galaxy',
    ];
    private const MODES = ['light', 'dark', 'contrast'];
    private const GEOMETRIES = ['soft', 'sharp', 'pill', 'organic'];
    private const BACKGROUNDS = ['aurora', 'mesh', 'waves', 'orbs', 'grid', 'static'];

    public function __construct(private readonly PreferencesRepositoryInterface $preferences)
    {
    }

    /** @return array<string, mixed> */
    public function get(User $actor): array
    {
        return $this->preferences->get($actor->id);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function save(User $actor, array $data): array
    {
        if (isset($data['theme']) && !in_array($data['theme'], self::THEMES, true)) {
            throw HttpException::unprocessable('Tema inválido.');
        }
        if (isset($data['mode']) && !in_array($data['mode'], self::MODES, true)) {
            throw HttpException::unprocessable('Modo inválido.');
        }
        if (isset($data['geometry']) && !in_array($data['geometry'], self::GEOMETRIES, true)) {
            throw HttpException::unprocessable('Geometría inválida.');
        }
        if (isset($data['background']) && !in_array($data['background'], self::BACKGROUNDS, true)) {
            throw HttpException::unprocessable('Fondo inválido.');
        }

        $this->preferences->save($actor->id, $data);

        return $this->preferences->get($actor->id);
    }
}
