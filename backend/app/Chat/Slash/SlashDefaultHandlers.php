<?php

namespace App\Chat\Slash;

/**
 * Базові обробники T66; семантика решти команд — T67+.
 */
final class SlashDefaultHandlers
{
    public static function me(SlashInvocation $inv): SlashHandlerResult
    {
        $body = $inv->parsed->args;
        $formatted = '*'.$inv->user->user_name.($body === '' ? '' : ' '.$body).'*';

        return SlashHandlerResult::roomMessage($formatted, [
            'name' => 'me',
            'recognized' => true,
            'result' => 'public_message',
        ]);
    }

    public static function noop(SlashInvocation $inv): SlashHandlerResult
    {
        return SlashHandlerResult::clientOnly([], [
            'name' => 'noop',
            'recognized' => true,
            'result' => 'client_only',
        ]);
    }

    public static function unknown(SlashInvocation $inv): SlashHandlerResult
    {
        $cmd = $inv->parsed->command;
        $label = $cmd === '' ? '(порожня)' : '/'.$cmd;

        return SlashHandlerResult::clientOnly(
            ['Невідома команда: '.$label.'. Спробуйте /manual (буде в T67).'],
            [
                'name' => $cmd === '' ? null : $cmd,
                'recognized' => false,
                'result' => 'client_only',
            ],
        );
    }
}
