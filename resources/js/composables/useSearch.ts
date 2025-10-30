import { ref, watch } from 'vue'
import { pickBy, throttle } from 'lodash'
import { router } from '@inertiajs/vue3'

export function useSearch(routeName: string, initialSearch = '', data = [], routeParam = {}) {
    const search = ref(initialSearch)

    const throttledSearch = throttle(() => {
        const currentParams = route().params ? { ...pickBy(route().params) } : {}

        if (search.value && search.value !== '') {
            currentParams.search = search.value
        } else {
            delete currentParams.search
        }

        router.get(route(routeName, routeParam), currentParams, {
            only: data,
            preserveState: true,
            preserveScroll: true,
        })
    }, 500)

    watch(search, throttledSearch)

    return { search }
}
