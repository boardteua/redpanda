---
name: vuejs-expert
description: Expert guidance for Vue 2.7 / Vue 2 apps—SFCs, Options API, reactivity, vue-router@3, and Vuex 3 (state, getters, mutations, actions, modules, plugins). Use when implementing or debugging Vue 2 UI, stores, routing, Vite+Vue2 builds, or when the user asks for Vue.js / Vue 2 expertise.
---

# Vue.js expert (Vue 2.x)

## Scope

- **Vue 2.7+** where possible (built-in `composition-api` subset); default to **Options API** if the codebase does.
- **Router**: vue-router **v3** (Vue 2).
- **State**: **Vuex 3** with Vue 2 (`vuex@3`).
- Align with **this repo**: Laravel + Vite; Vue SPA under `backend/resources/js/` (see existing components and `frontend-developer` skill for general UI rules).

For official topic → URL mapping and deeper notes, see [reference.md](reference.md). For library API details, use **Context7** (Vue 2, Vuex 3, vue-router 3) when generating or verifying code.

## When to use

- Vue 2 SFCs, props/events, slots, lifecycle, watchers, `provide`/`inject`
- Vuex store design, modules, namespacing, async actions, strict mode
- vue-router navigation guards, lazy routes, meta fields
- Vite + `@vitejs/plugin-vue2` or Vue CLI–era patterns in a Vue 2 codebase
- Debugging reactivity, store commits/dispatches, or router edge cases

## Vuex 3 essentials (Vue 2)

**Concepts**

- **Store**: single reactive state tree (per store instance).
- **State**: data; reactive like component `data`.
- **Getters**: derived state (store-level computed).
- **Mutations**: **synchronous** transitions; the only way to change state in strict mode (aside from initial state).
- **Actions**: **async** orchestration; commit mutations.
- **Modules**: split large stores; use namespacing when scaling.

**Install**

```bash
npm install vuex@3
```

**Minimal store**

```javascript
// store/index.js
import Vue from 'vue'
import Vuex from 'vuex'

Vue.use(Vuex)

export default new Vuex.Store({
  state: { count: 0 },
  mutations: {
    increment(state) {
      state.count++
    }
  },
  actions: {
    incrementAsync({ commit }) {
      return Promise.resolve().then(() => commit('increment'))
    }
  },
  getters: {
    double: (state) => state.count * 2
  }
})
```

**Bootstrap**

```javascript
import store from './store'

new Vue({
  store,
  render: (h) => h(App)
}).$mount('#app')
```

**In components**

- `this.$store.state`, `this.$store.getters`
- `this.$store.commit('mutation', payload)`
- `this.$store.dispatch('action', payload)`
- `mapState`, `mapGetters`, `mapMutations`, `mapActions` from `vuex` for concise declarations

## Best practices (Vuex)

1. Mutations stay **sync**; put async in **actions**.
2. Prefer **getters** for derived data instead of duplicating logic in components.
3. **Normalize** nested entity state when lists/IDs grow (easier updates, fewer bugs).
4. Use **modules** (and **namespaced** modules) as the store grows.
5. Keep **mutation/action names** consistent and intention-revealing.
6. If the project adds TypeScript, type store modules and payloads where it helps.

## Vue 2.7 note

Vue 2.7 backports some Composition API; use it only when the repo already does. Otherwise follow existing **Options API** patterns for consistency.

## Anti-patterns to flag

- Mutations performing async work or API calls
- Mutating `state` outside mutations (breaks strict mode and devtools)
- Giant flat stores without modules when features are independent
- Duplicating store-derived values in component `data` instead of getters

## Additional resources

- [reference.md](reference.md) — official doc links and topic index
- Project stack reminder: **Vue 2.7**, **vue-router@3**, **Vite** — match imports and APIs to those versions
