<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

use App\Entity\PadelMatch;
use App\Entity\PadelMatchComment;
use App\Entity\User;

class PadelMatchCommentUnitTest extends TestCase
{
    private PadelMatchComment $comment;

    protected function setUp(): void
    {
        $this->comment = new PadelMatchComment();
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->comment->getId());
    }

    public function testPadelMatchIsNullByDefault(): void
    {
        $this->assertNull($this->comment->getPadelMatch());
    }

    public function testSetAndGetPadelMatch(): void
    {
        $match = new PadelMatch();
        $this->comment->setPadelMatch($match);

        $this->assertSame($match, $this->comment->getPadelMatch());
    }

    public function testUserIsNullByDefault(): void
    {
        $this->assertNull($this->comment->getUser());
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $this->comment->setUser($user);

        $this->assertSame($user, $this->comment->getUser());
    }

    public function testContentIsNullByDefault(): void
    {
        $this->assertNull($this->comment->getContent());
    }

    public function testSetAndGetContent(): void
    {
        $this->comment->setContent('Geweldige wedstrijd!');

        $this->assertSame('Geweldige wedstrijd!', $this->comment->getContent());
    }

    public function testCreatedAtIsNullByDefault(): void
    {
        $this->assertNull($this->comment->getCreatedAt());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $date = new \DateTime('2024-06-01 12:00:00');
        $this->comment->setCreatedAt($date);

        $this->assertSame($date, $this->comment->getCreatedAt());
    }

}
