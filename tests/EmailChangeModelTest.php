<?php

namespace MilenMk\LaravelEmailChangeConfirmation\Tests;

use MilenMk\LaravelEmailChangeConfirmation\Models\EmailChange;

class EmailChangeModelTest extends TestCase
{
    public function testCanCreateEmailChangeRecord()
    {
        $user = $this->createUser();
        
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'new@example.com',
        ]);

        $this->assertInstanceOf(EmailChange::class, $emailChange);
        $this->assertEquals($user->id, $emailChange->user_id);
        $this->assertEquals('old@example.com', $emailChange->current_email);
        $this->assertEquals('new@example.com', $emailChange->new_email);
        $this->assertTrue($emailChange->isPending());
    }

    public function testCanConfirmEmailChange()
    {
        $user = $this->createUser();
        
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'new@example.com',
        ]);

        $result = $emailChange->confirm();

        $this->assertTrue($result);
        $this->assertTrue($emailChange->isConfirmed());
        $this->assertFalse($emailChange->isPending());
        $this->assertNotNull($emailChange->change_confirmed_at);
    }

    public function testCanDenyEmailChange()
    {
        $user = $this->createUser();
        
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'new@example.com',
        ]);

        $result = $emailChange->deny();

        $this->assertTrue($result);
        $this->assertTrue($emailChange->isDenied());
        $this->assertFalse($emailChange->isPending());
        $this->assertNotNull($emailChange->change_denied_at);
    }

    public function testHasUserRelationship()
    {
        $user = $this->createUser(['name' => 'John Doe']);
        
        $emailChange = EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'new@example.com',
        ]);

        $this->assertEquals('John Doe', $emailChange->user->name);
    }

    public function testCanScopePendingChanges()
    {
        $user = $this->createUser();
        
        // Create pending change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'pending@example.com',
        ]);

        // Create confirmed change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'confirmed@example.com',
            'change_confirmed_at' => now(),
        ]);

        // Create denied change
        EmailChange::create([
            'user_id' => $user->id,
            'current_email' => 'old@example.com',
            'new_email' => 'denied@example.com',
            'change_denied_at' => now(),
        ]);

        $pendingChanges = EmailChange::pending()->get();
        $confirmedChanges = EmailChange::confirmed()->get();
        $deniedChanges = EmailChange::denied()->get();

        $this->assertCount(1, $pendingChanges);
        $this->assertCount(1, $confirmedChanges);
        $this->assertCount(1, $deniedChanges);
        
        $this->assertEquals('pending@example.com', $pendingChanges->first()->new_email);
        $this->assertEquals('confirmed@example.com', $confirmedChanges->first()->new_email);
        $this->assertEquals('denied@example.com', $deniedChanges->first()->new_email);
    }
}