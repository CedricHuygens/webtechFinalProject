<?php

namespace App\Tests;

use App\Entity\PadelMatchComment;
use App\Entity\PadelMatchCommentLike;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class PadelMatchCommentLikeUnitTest extends TestCase
{
    private PadelMatchCommentLike $like;

    protected function setUp(): void
    {
        $this->like = new PadelMatchCommentLike();
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->like->getId());
    }

    public function testCommentIsNullByDefault(): void
    {
        $this->assertNull($this->like->getComment());
    }

    public function testSetAndGetComment(): void
    {
        $comment = new PadelMatchComment();
        $this->like->setComment($comment);

        $this->assertSame($comment, $this->like->getComment());
    }

    public function testUserIsNullByDefault(): void
    {
        $this->assertNull($this->like->getUser());
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $this->like->setUser($user);

        $this->assertSame($user, $this->like->getUser());
    }

}
