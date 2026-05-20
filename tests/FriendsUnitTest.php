<?php

use App\Entity\FriendRequest;
use App\Entity\User;
use App\Entity\Friend;
use PHPUnit\Framework\TestCase;

class FriendsUnitTest extends TestCase
{
    public function testFriendRequestSenderUserGetterSetter(): void
    {
        $friendRequest = new FriendRequest();
        $sender = new User();

        $friendRequest->setSenderUser($sender);

        $this->assertSame($sender, $friendRequest->getSenderUser());
    }

    public function testFriendRequestReceiverUserGetterSetter(): void
    {
        $friendRequest = new FriendRequest();
        $receiver = new User();

        $friendRequest->setReceiverUser($receiver);

        $this->assertSame($receiver, $friendRequest->getReceiverUser());
    }

    public function testFriendRequestCreatedAtGetterSetter(): void
    {
        $friendRequest = new FriendRequest();
        $date = new \DateTimeImmutable();

        $friendRequest->setCreatedAt($date);

        $this->assertSame($date, $friendRequest->getCreatedAt());
    }

    public function testFriendRequestIdIsNullByDefault(): void
    {
        $friendRequest = new FriendRequest();

        $this->assertNull($friendRequest->getId());
    }


    public function testFriendUser1GetterSetter(): void
    {
        $friend = new Friend();
        $user1 = new User();

        $friend->setUser1($user1);

        $this->assertSame($user1, $friend->getUser1());
    }

    public function testFriendUser2GetterSetter(): void
    {
        $friend = new Friend();
        $user2 = new User();

        $friend->setUser2($user2);

        $this->assertSame($user2, $friend->getUser2());
    }

    public function testFriendIdIsNullByDefault(): void
    {
        $friend = new Friend();

        $this->assertNull($friend->getId());
    }

}
