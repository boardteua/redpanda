<?php

namespace App\Chat\SlashCommands\Handlers;

use App\Chat\SlashCommands\Contracts\SlashCommandHandlerContract;
use App\Chat\SlashCommands\SlashCommandContext;
use App\Chat\SlashCommands\SlashCommandOutcome;
use App\Models\Friendship;
use App\Models\User;

final class FriendSlashCommandHandler implements SlashCommandHandlerContract
{
    public function handle(SlashCommandContext $context, string $commandName, string $args): SlashCommandOutcome
    {
        if ($context->user->guest) {
            return SlashCommandOutcome::httpError(403, 'Команда /friend доступна лише зареєстрованим користувачам.');
        }

        $trimmed = trim($args);
        if ($trimmed === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /friend нік');
        }

        $nick = preg_split('/\s+/u', $trimmed, 2)[0] ?? '';
        if ($nick === '') {
            return SlashCommandOutcome::httpError(422, 'Вкажіть нік: /friend нік');
        }

        $target = User::query()
            ->whereRaw('LOWER(user_name) = LOWER(?)', [$nick])
            ->first();

        if ($target === null) {
            return SlashCommandOutcome::httpError(422, 'Користувача з таким ніком не знайдено.');
        }

        $self = $context->user;

        if ((int) $target->id === (int) $self->id) {
            return SlashCommandOutcome::httpError(422, 'Неможливо додати себе.');
        }

        if ($target->guest) {
            return SlashCommandOutcome::httpError(422, 'Неможливо додати гостя до друзів.');
        }

        $forward = Friendship::query()
            ->where('requester_id', $self->id)
            ->where('addressee_id', $target->id)
            ->first();

        if ($forward !== null) {
            if ($forward->status === Friendship::STATUS_PENDING) {
                return SlashCommandOutcome::httpError(422, 'Запит уже надіслано.');
            }
            if ($forward->status === Friendship::STATUS_ACCEPTED) {
                return SlashCommandOutcome::httpError(422, 'Вже у списку друзів.');
            }
            $forward->update(['status' => Friendship::STATUS_PENDING]);

            return SlashCommandOutcome::clientOnlyMessage('Запит на додавання до друзів надіслано повторно.', [
                'name' => 'friend',
                'recognized' => true,
            ]);
        }

        $reverse = Friendship::query()
            ->where('requester_id', $target->id)
            ->where('addressee_id', $self->id)
            ->first();

        if ($reverse !== null) {
            if ($reverse->status === Friendship::STATUS_PENDING) {
                return SlashCommandOutcome::httpError(409, 'Цей користувач уже надіслав вам запит — прийміть його у вкладці «Запити».');
            }
            if ($reverse->status === Friendship::STATUS_ACCEPTED) {
                return SlashCommandOutcome::httpError(422, 'Вже у списку друзів.');
            }
        }

        Friendship::query()->create([
            'requester_id' => $self->id,
            'addressee_id' => $target->id,
            'status' => Friendship::STATUS_PENDING,
        ]);

        return SlashCommandOutcome::clientOnlyMessage('Запит на додавання до друзів надіслано.', [
            'name' => 'friend',
            'recognized' => true,
        ]);
    }
}
