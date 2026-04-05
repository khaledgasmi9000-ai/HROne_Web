<?php

namespace App\Dto\Community;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO d’entrée pour la création de post — validé par le composant Validator (bundle symfony/validator).
 */
class CreatePostInput
{
    #[Assert\NotBlank(message: 'Champ requis: title.')]
    #[Assert\Length(max: 255)]
    public string $title = '';

    #[Assert\Length(max: 10000)]
    public ?string $description = null;

    #[Assert\Length(max: 80)]
    public ?string $tag = null;

    /** @var string|null URL absolue http(s), ou null si aucune image */
    #[Assert\Length(max: 2048)]
    #[Assert\Url(requireTld: false, protocols: ['http', 'https'])]
    public ?string $image_url = null;

    /**
     * @param array<string, mixed> $data Corps JSON décodé
     */
    public static function fromRequestArray(array $data): self
    {
        $o = new self();
        $o->title = isset($data['title']) ? trim((string) $data['title']) : '';
        $dr = $data['description'] ?? null;
        $o->description = $dr !== null && (string) $dr !== '' ? (string) $dr : null;
        $tg = isset($data['tag']) ? trim((string) $data['tag']) : '';
        $o->tag = $tg !== '' ? $tg : null;
        $iu = isset($data['image_url']) ? trim((string) $data['image_url']) : '';
        $o->image_url = $iu !== '' ? $iu : null;

        return $o;
    }
}
