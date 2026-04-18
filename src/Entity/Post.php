<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use App\Repository\PostRepository;

#[Vich\Uploadable]
#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'posts')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $user_id = null;

    public function getUser_id(): ?int
    {
        return $this->user_id;
    }

    public function setUser_id(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $title = null;

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $image_url = null;

    #[Vich\UploadableField(mapping: 'post_image', fileNameProperty: 'image_url')]
    private ?File $imageFile = null;

    public function getImage_url(): ?string
    {
        return $this->image_url;
    }

    public function setImage_url(?string $image_url): self
    {
        $this->image_url = $image_url;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $tag = null;

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_active = null;

    public function is_active(): ?bool
    {
        return $this->is_active;
    }

    public function setIs_active(?bool $is_active): self
    {
        $this->is_active = $is_active;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->image_url;
    }

    public function setImageUrl(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }

    public function setImageFile(?File $imageFile = null): self
    {
        $this->imageFile = $imageFile;

        if ($imageFile !== null) {
            // Triggers Doctrine change tracking so Vich can upload the file.
            $this->created_at = new \DateTime();
        }

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(?bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTime $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    /**
     * @var Collection<int, PostVote>
     */
    #[ORM\OneToMany(targetEntity: PostVote::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    private Collection $votes;

    /**
     * @var Collection<int, PostFavorite>
     */
    #[ORM\OneToMany(targetEntity: PostFavorite::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    private Collection $favorites;

    public function __construct()
    {
        $this->votes = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    /**
     * @return Collection<int, PostVote>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function addVote(PostVote $vote): static
    {
        if (!$this->votes->contains($vote)) {
            $this->votes->add($vote);
            $vote->setPost($this);
        }

        return $this;
    }

    public function removeVote(PostVote $vote): static
    {
        if ($this->votes->removeElement($vote)) {
            if ($vote->getPost() === $this) {
                $vote->setPost(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostFavorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(PostFavorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setPost($this);
        }

        return $this;
    }

    public function removeFavorite(PostFavorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getPost() === $this) {
                $favorite->setPost(null);
            }
        }

        return $this;
    }

    public static function hydrateAndValidate(Request $request): array
    {
        $title = trim((string) $request->request->get('title'));
        $description = trim((string) $request->request->get('description'));
        $imageFile = $request->files->get('image_file');
        $tag = trim((string) $request->request->get('tag'));
        $isActive = $request->request->has('is_active');

        if ($title === '' || $description === '') {
            return [[], 'Titre et description sont obligatoires.'];
        }
        if (mb_strlen($title) < 3) {
            return [[], 'Le titre doit contenir au moins 3 caracteres.'];
        }
        if (mb_strlen($description) < 5) {
            return [[], 'La description doit contenir au moins 5 caracteres.'];
        }
        if ($imageFile !== null) {
            $mime = (string) $imageFile->getMimeType();
            if (strpos($mime, 'image/') !== 0) {
                return [[], 'Le fichier uploade doit etre une image.'];
            }
        }
        if ($tag !== '' && mb_strlen($tag) < 2) {
            return [[], 'Le tag doit contenir au moins 2 caracteres.'];
        }

        return [[
            'title' => $title,
            'description' => $description,
            'tag' => $tag,
            'is_active' => $isActive,
        ], null];
    }

}



