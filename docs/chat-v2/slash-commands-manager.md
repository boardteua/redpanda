# Slash-команди: менеджер/реєстр (T73)

## Мета

Додавати нові slash-команди **без** правок “ядра” обробника повідомлень: команда реєструється в коді, має єдиний контракт обробника, а `POST /api/v1/rooms/{room}/messages` делегує виконання в реєстр.

## Де це в коді

- Реєстр: `backend/app/Chat/SlashCommands/SlashCommandRegistry.php`
- Процесор: `backend/app/Chat/SlashCommands/SlashCommandProcessor.php`
- Контракт handler’а: `backend/app/Chat/SlashCommands/Contracts/SlashCommandHandlerContract.php`
- Реєстрація базових команд: `backend/app/Providers/AppServiceProvider.php`

## Як додати команду

### Варіант A — invokable class (рекомендовано)

1. Створи handler, що імплементує `SlashCommandHandlerContract`.
2. Зареєструй у `AppServiceProvider`:

```php
$registry->register('mycmd', $app->make(MyCmdSlashCommandHandler::class), [
    'description' => 'Короткий опис (для /manual або довідника)',
    'roles' => ['registered'], // або ['chat-admin'], тощо
    'priority' => 0,
]);
```

### Варіант B — callable (зручно для простих/тестових команд)

```php
$registry->register('mycmd', static function ($context, string $name, string $args) {
    return \App\Chat\SlashCommands\SlashCommandOutcome::clientOnlyMessage('ok', [
        'name' => $name,
        'recognized' => true,
    ]);
});
```

## Колізії імен

Якщо команда з тим самим ім’ям зареєстрована кілька разів, реєстр обирає визначення з найбільшим `meta.priority` (за замовчуванням `0`).

## Безпека (важливе)

- Реєстр **не** виконує PHP-код з БД або користувацького вводу: handler’и мають бути **зареєстровані в коді**.
- У логах (`chat.slash_command`) фіксується назва команди і `user_id`/`room_id` без секретів.

