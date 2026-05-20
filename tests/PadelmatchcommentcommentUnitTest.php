<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;

use App\Entity\PadelMatchComment;
use App\Entity\PadelMatchCommentComment;

class PadelMatchCommentCommentUnitTest extends TestCase
{
    private PadelMatchCommentComment $commentComment;

    protected function setUp(): void
    {
        $this->commentComment = new PadelMatchCommentComment();
    }

    public function testIdIsNullByDefault(): void
    {
        $this->assertNull($this->commentComment->getId());
    }

    public function testMainCommentIsNullByDefault(): void
    {
        $this->assertNull($this->commentComment->getMainComment());
    }

    public function testSetAndGetMainComment(): void
    {
        $main = new PadelMatchComment();
        $this->commentComment->setMainComment($main);

        $this->assertSame($main, $this->commentComment->getMainComment());
    }

    public function testReplyCommentIsNullByDefault(): void
    {
        $this->assertNull($this->commentComment->getReplyComment());
    }

    public function testSetAndGetReplyComment(): void
    {
        $reply = new PadelMatchComment();
        $this->commentComment->setReplyComment($reply);

        $this->assertSame($reply, $this->commentComment->getReplyComment());
    }
}
