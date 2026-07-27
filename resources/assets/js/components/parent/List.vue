<template>
    <div>
        <portal to="parent_index">
            <div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Parents</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">View, search and manage all parents.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a :href="url+'/admin/parents'" class="ds-btn ds-btn-ghost text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 4v6h6M23 20v-6h-6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                            Reset
                        </a>
                    </div>
                </div>

                <!-- Search / Filter Bar -->
                <div class="ds-card mb-4">
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <label class="ds-label">Search by name</label>
                            <input type="text" v-model="fullname" @input="onFilterChange" placeholder="Search parent name..." class="ds-form-input">
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="ds-label">Mobile number</label>
                            <input type="text" v-model="mobile_no" @input="onFilterChange" placeholder="Search mobile..." class="ds-form-input">
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="ds-label">Student name</label>
                            <input type="text" v-model="student_name" @input="onFilterChange" placeholder="Search student..." class="ds-form-input">
                        </div>
                    </div>
                </div>
            </div>
        </portal>

        <!-- Data Table -->
        <div class="dashboard-shell dashboard-shell--admin px-4 md:px-6 py-4" style="padding-top: 0;">
            <div v-if="isLoading" class="text-center py-12 text-gray-500">Loading parents...</div>

            <div v-else-if="!rows.length" class="ds-table-empty">
                <div class="ds-empty-state-icon">👤</div>
                <p class="ds-empty-state-title">No parents found</p>
                <p class="ds-empty-state-desc">Try adjusting your search or filter criteria.</p>
            </div>

            <div v-else class="ds-table-wrap">
                <table class="ds-table-ledger ds-table-card-mobile">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Parent Of</th>
                            <th>Mobile Number</th>
                            <th style="text-align: center; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(row, index) in rows" :key="row.id">
                            <td data-label="#" class="text-gray-400 text-xs font-mono">{{ index + 1 + (page - 1) * 10 }}</td>
                            <td data-label="Name">
                                <a :href="row.showurl" class="dt-name-link">
                                    <span class="font-medium text-gray-900">{{ row.fullname }}</span>
                                    <span v-if="row.status != 'active'" class="dt-badge dt-badge-inactive ml-2">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        Inactive
                                    </span>
                                </a>
                            </td>
                            <td data-label="Parent Of">
                                <a :href="row.children.name" class="text-sm text-gray-700 hover:text-blue-600 transition-colors">
                                    {{ row.children.fullname }}
                                </a>
                            </td>
                            <td data-label="Mobile Number">
                                <span class="text-sm text-gray-600">{{ row.mobile_no }}</span>
                            </td>
                            <td data-label="Actions" style="text-align: center;">
                                <a :href="row.editurl" class="dt-action-btn" title="Edit">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between" v-if="rows.length && page_count > 1">
                <div class="text-sm text-gray-600">
                    Page {{ page }} of {{ page_count }} ({{ totalRecords }} total)
                </div>
                <div class="flex items-center gap-1">
                    <button @click="changePage(1)" :disabled="page <= 1" class="ds-btn ds-btn-ghost ds-btn-sm" :class="{'opacity-40 cursor-not-allowed': page <= 1}" title="First page">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="11 17 6 12 11 7"/><polyline points="18 17 13 12 18 7"/></svg>
                    </button>
                    <button @click="changePage(page - 1)" :disabled="page <= 1" class="ds-btn ds-btn-ghost ds-btn-sm" :class="{'opacity-40 cursor-not-allowed': page <= 1}" title="Previous">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <span v-for="p in pageRange" :key="p">
                        <button @click="changePage(p)" class="ds-btn ds-btn-sm min-w-[36px]" :class="p === page ? 'ds-btn-primary' : 'ds-btn-ghost'">
                            {{ p }}
                        </button>
                    </span>
                    <button @click="changePage(page + 1)" :disabled="page >= page_count" class="ds-btn ds-btn-ghost ds-btn-sm" :class="{'opacity-40 cursor-not-allowed': page >= page_count}" title="Next">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                    <button @click="changePage(page_count)" :disabled="page >= page_count" class="ds-btn ds-btn-ghost ds-btn-sm" :class="{'opacity-40 cursor-not-allowed': page >= page_count}" title="Last page">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="13 17 18 12 13 7"/><polyline points="6 17 11 12 6 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['url', 'searchquery'],
        data() {
            return {
                rows: [],
                totalRecords: 0,
                page: 1,
                page_count: 0,
                isLoading: false,
                fullname: '',
                mobile_no: '',
                student_name: '',
                debouncedSearch: null,
            }
        },
        computed: {
            pageRange() {
                const range = [];
                const start = Math.max(1, this.page - 2);
                const end = Math.min(this.page_count, this.page + 2);
                for (let i = start; i <= end; i++) {
                    range.push(i);
                }
                return range;
            }
        },
        methods: {
            getData() {
                this.isLoading = true;
                axios.get('/admin/parent/list?' +
                    'fullname=' + encodeURIComponent(this.fullname) +
                    '&student_name=' + encodeURIComponent(this.student_name) +
                    '&mobile_no=' + encodeURIComponent(this.mobile_no) +
                    '&page=' + this.page
                ).then(response => {
                    this.rows = response.data.data;
                    this.page_count = response.data.meta.last_page;
                    this.totalRecords = response.data.meta.total;
                    this.isLoading = false;
                }).catch(() => {
                    this.isLoading = false;
                });
            },
            onFilterChange() {
                this.debouncedSearch();
            },
            changePage(p) {
                if (p < 1 || p > this.page_count || p === this.page) return;
                this.page = p;
                this.getData();
            },
        },
        created() {
            this.debouncedSearch = _.debounce(function() {
                this.page = 1;
                this.getData();
            }, 300);
            this.getData();
        },
    }
</script>
