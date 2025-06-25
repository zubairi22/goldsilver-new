import { ref, watch } from 'vue';
import { throttle } from 'lodash';
import { router } from '@inertiajs/vue3';

export function useSearch(routeName: string, initialSearch = '', data: any = [], routeParam?: any,) {
    const search = ref(initialSearch);

    const throttledSearch = throttle(() => {
        if (search.value) {
            router.get(route(routeName, routeParam), { search: search.value }, {
                only: data,
                preserveState: true,
                preserveScroll: true,
            });
        } else {
            router.get(route(routeName, routeParam));
        }
    }, 500);

    watch(search, () => {
        throttledSearch();
    });

    return {
        search,
    };
}
