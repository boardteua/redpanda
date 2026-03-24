/**
 * T103: автокомпліт ніка для привату (вкладка «Люди») — методи для ChatRoom.vue.
 */
export const chatRoomPeerAutocompleteMethods = {
    clearPeerAutocompleteUi() {
        this.peerAutocompleteSuggestions = [];
        this.peerAutocompleteHighlightIndex = -1;
        this.peerAutocompleteLoading = false;
        this.peerAutocompleteRequestSeq += 1;
        if (this.peerLookupDebounceTimer) {
            clearTimeout(this.peerLookupDebounceTimer);
            this.peerLookupDebounceTimer = null;
        }
    },
    schedulePeerAutocompleteFetch() {
        if (this.sidebarTab !== 'users') {
            return;
        }
        if (this.peerLookupDebounceTimer) {
            clearTimeout(this.peerLookupDebounceTimer);
        }
        this.peerLookupDebounceTimer = setTimeout(() => {
            this.peerLookupDebounceTimer = null;
            const t = String(this.peerLookupName || '').trim();
            this.peerAutocompleteHighlightIndex = -1;
            if (t.length < 2) {
                this.peerAutocompleteSuggestions = [];
                this.peerAutocompleteLoading = false;
                return;
            }
            this.runPeerAutocompleteFetch(t);
        }, 400);
    },
    async runPeerAutocompleteFetch(q) {
        this.peerAutocompleteLoading = true;
        const seq = ++this.peerAutocompleteRequestSeq;
        try {
            const { data } = await window.axios.get('/api/v1/users/autocomplete', {
                params: { q },
            });
            if (seq !== this.peerAutocompleteRequestSeq) {
                return;
            }
            this.peerAutocompleteSuggestions = Array.isArray(data.data) ? data.data : [];
        } catch {
            if (seq !== this.peerAutocompleteRequestSeq) {
                return;
            }
            this.peerAutocompleteSuggestions = [];
        } finally {
            if (seq === this.peerAutocompleteRequestSeq) {
                this.peerAutocompleteLoading = false;
            }
        }
    },
    onPeerLookupKeydown(e) {
        if (!e || !this.user) {
            return;
        }
        const list = this.peerAutocompleteSuggestions || [];
        const panelOpen = this.peerAutocompletePanelOpen;
        if (e.key === 'Escape') {
            if (panelOpen) {
                e.preventDefault();
                this.peerAutocompleteSuggestions = [];
                this.peerAutocompleteHighlightIndex = -1;
                this.peerAutocompleteLoading = false;
            }
            return;
        }
        if (e.key === 'ArrowDown') {
            if (!panelOpen || list.length === 0) {
                return;
            }
            e.preventDefault();
            const next =
                this.peerAutocompleteHighlightIndex < 0
                    ? 0
                    : Math.min(this.peerAutocompleteHighlightIndex + 1, list.length - 1);
            this.peerAutocompleteHighlightIndex = next;
            return;
        }
        if (e.key === 'ArrowUp') {
            if (!panelOpen || list.length === 0) {
                return;
            }
            e.preventDefault();
            if (this.peerAutocompleteHighlightIndex <= 0) {
                this.peerAutocompleteHighlightIndex = 0;
            } else {
                this.peerAutocompleteHighlightIndex -= 1;
            }
            return;
        }
        if (e.key === 'Enter') {
            if (panelOpen && list.length > 0 && this.peerAutocompleteHighlightIndex >= 0) {
                e.preventDefault();
                this.pickPeerAutocomplete(list[this.peerAutocompleteHighlightIndex]);
                return;
            }
            if (panelOpen && list.length === 1 && this.peerAutocompleteHighlightIndex < 0) {
                e.preventDefault();
                this.pickPeerAutocomplete(list[0]);
                return;
            }
            e.preventDefault();
            this.lookupAndOpenPrivate();
        }
    },
    pickPeerAutocomplete(peer) {
        if (!peer || peer.id == null) {
            return;
        }
        this.openPrivatePeer(peer);
        this.peerLookupName = '';
        this.clearPeerAutocompleteUi();
        this.loadError = '';
    },
};
