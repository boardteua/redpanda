/**
 * Початковий стан бічної панелі (друзі, приват, ігнор).
 * Поля мають збігатися з використанням у шаблоні — не прибирати точково при merge.
 * Прапор echoUserListenerReady додатково скидається в setupEcho при створенні нового Echo (HMR тощо).
 */
export function createChatRoomSidebarState() {
    return {
        peerLookupName: '',
        /** T85: автокомпліт ніків для привату */
        peerAutocompleteSuggestions: [],
        peerAutocompleteHighlightIndex: -1,
        peerAutocompleteLoading: false,
        peerLookupDebounceTimer: null,
        peerAutocompleteRequestSeq: 0,
        conversations: [],
        friendsAccepted: [],
        friendsIncoming: [],
        friendsOutgoing: [],
        ignores: [],
        privatePeer: null,
        privateMessages: [],
        privateMessageIds: new Set(),
        privateComposerText: '',
        loadingPrivateMessages: false,
        sendingPrivate: false,
        privateLoadError: '',
        echoUserListenerReady: false,
        privateListLoadError: '',
        friendsIgnoresLoadError: '',
        /** T56: сума непрочитаних вхідних приватних (з meta GET /private/conversations). */
        totalPrivateUnread: 0,
        /** T65: зняти listeners активації звуку в beforeDestroy. */
        chatSoundActivateHandler: null,
    };
}
