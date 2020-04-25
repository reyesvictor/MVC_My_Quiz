<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\HistoricRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Historic
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="historics")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Quiz", inversedBy="historics")
     * @ORM\JoinColumn(nullable=false)
     */
    private $quiz;

    /**
     * @ORM\Column(type="array")
     */
    private $answers = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $score;

    /**
     * @ORM\Column(type="boolean")
     */
    private $succeeded;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * 
     * @ORM\PrePersist
     * @ORM\PreUpdate
     * 
     * @return void
     */
    public function createSlug()
    {
        if (empty($this->slug)) {
            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->name);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?User
    {
        return $this->user;
    }

    public function setUserId(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getQuizId(): ?Quiz
    {
        return $this->quiz;
    }

    public function setQuizId(?Quiz $quiz): self
    {
        $this->quiz = $quiz;

        return $this;
    }

    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers): self
    {
        $this->answers = $answers;

        return $this;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;

        return $this;
    }

    public function getSucceeded(): ?bool
    {
        return $this->succeeded;
    }

    public function setSucceeded(bool $succeeded): self
    {
        $this->succeeded = $succeeded;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    /**
     * @ORM\PrePersist
     * 
     * @return void
     */
    public function setCreatedAt()
    {
        if (empty($this->created_at)) {
            $this->created_at = new \DateTime();
        }
    }
}
