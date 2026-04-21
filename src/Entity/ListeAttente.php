<?php

namespace App\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\ListeAttenteRepository::class)]
#[ORM\Table(name: 'liste_attente')]
class ListeAttente
{
    #[ORM\Id]
    #[ORM\Column(name: 'ID_Attente', type: 'integer')]
    private ?int $ID_Attente = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class)]
    #[ORM\JoinColumn(name: 'ID_Evenement', referencedColumnName: 'ID_Evenement', nullable: false)]
    private ?Evenement $evenement = null;

    #[ORM\ManyToMany(targetEntity: Activite::class)]
    #[ORM\JoinTable(name: 'liste_attente_activite')]
    #[ORM\JoinColumn(name: 'attente_id', referencedColumnName: 'ID_Attente', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'activite_id', referencedColumnName: 'ID_Activite')]
    private Collection $activites;

    public function __construct()
    {
        $this->activites = new ArrayCollection();
    }

    #[ORM\Column(name: 'nom_complet', type: 'string', length: 255)]
    private ?string $nomComplet = null;

    #[ORM\Column(name: 'email', type: 'string', length: 255)]
    private ?string $email = null;

    #[ORM\Column(name: 'date_demande', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateDemande = null;


    public function getIdAttente(): ?int
    {
        return $this->ID_Attente;
    }

    public function setIdAttente(int $id): self
    {
        $this->ID_Attente = $id;
        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }

    /**
     * @return Collection<int, Activite>
     */
    public function getActivites(): Collection
    {
        return $this->activites;
    }

    public function addActivite(Activite $activite): self
    {
        if (!$this->activites->contains($activite)) {
            $this->activites->add($activite);
        }
        return $this;
    }

    public function removeActivite(Activite $activite): self
    {
        $this->activites->removeElement($activite);
        return $this;
    }

    public function getNomComplet(): ?string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): self
    {
        $this->nomComplet = $nomComplet;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getDateDemande(): ?\DateTimeInterface
    {
        return $this->dateDemande;
    }

    public function setDateDemande(?\DateTimeInterface $dateDemande): self
    {
        $this->dateDemande = $dateDemande;
        return $this;
    }
}
