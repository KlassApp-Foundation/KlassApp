<template>
    <div>
        <div
            v-if="this.success != null"
            class="alert alert-success"
            id="success-alert"
        >
            {{ this.success }}
        </div>

        <!-- Alphabet Filter -->
        <div class="my-4 filter-alphabet">
            <ul class="list-reset flex" style="max-width: calc(100vw - 40px); overflow: auto">
                <li v-for="alphabet in alphabets">
                    <a
                        href="#"
                        id="filter"
                        class="block font-bold p-2 bg-grey-light border border-grey mx-2 ni"
                        v-bind:class="letter === alphabet ? 'active' : 'text-blue'"
                        v-text="alphabet"
                        @click="sortMembers(alphabet)"
                    ></a>
                </li>
                <li>
                    <a
                        href="#"
                        class="block font-bold p-2 bg-grey-light border border-grey mx-2 ni"
                        @click="clearAll()"
                    >Clear All</a>
                </li>
            </ul>
            <div class="my-4" v-if="!filteredNames.length && users.length">
                No names for this letter
            </div>
        </div>

        <!-- Data Table -->
        <div class="ds-table-wrap">
            <table class="ds-table-ledger">
                <thead>
                    <tr>
                        <th class="dt-cell-check" style="width: 48px;">
                            <input
                                type="checkbox"
                                @click="selectAll($event)"
                                v-model="allSelected"
                                class="dt-checkbox"
                                style="width: 18px; height: 18px; cursor: pointer;"
                            />
                        </th>
                        <th>Name</th>
                        <th v-if="birthday == 'true'">Date of Birth</th>
                        <th>Designation</th>
                        <th>Status</th>
                        <th style="text-align: center; width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(user, index) in users" :key="user.id">
                        <!-- Checkbox -->
                        <td class="dt-cell-check" style="width: 48px;">
                            <input
                                type="checkbox"
                                :checked="selected.includes(user.id)"
                                @change="selectedCount(user.id, $event)"
                                class="dt-checkbox"
                                style="width: 18px; height: 18px; cursor: pointer;"
                            />
                        </td>
                        <!-- Name + link to profile -->
                        <td>
                            <a
                                :href="url + '/admin/staff/show/' + user['name']"
                                class="dt-name-link"
                            >
                                <span class="font-medium text-gray-900">{{ user['fullname'] }}</span>
                            </a>
                        </td>
                        <!-- Date of Birth (conditional) -->
                        <td v-if="birthday == 'true'">
                            <span class="text-sm text-gray-600">{{ user['date_of_birth'] }}</span>
                        </td>
                        <!-- Designation -->
                        <td>
                            <span class="text-sm text-gray-600">{{ user['designation_name'] }}</span>
                        </td>
                        <!-- Status Badge (icon + text, never color-only) -->
                        <td>
                            <span
                                v-if="user['status'] == 'active'"
                                class="dt-badge dt-badge-active"
                            >
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                    <polyline points="22 4 12 14.01 9 11.01"/>
                                </svg>
                                Active
                            </span>
                            <span
                                v-else
                                class="dt-badge dt-badge-inactive"
                            >
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                                Inactive
                            </span>
                        </td>
                        <!-- Actions -->
                        <td style="text-align: center;">
                            <a
                                :href="url + '/admin/staff/show/' + user['name']"
                                class="dt-action-btn"
                                title="View profile"
                            >
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    <!-- Empty state -->
                    <tr v-if="!users.length">
                        <td :colspan="birthday == 'true' ? 6 : 5" class="text-center py-12 text-gray-500">
                            No staff members found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Send Message Button -->
        <div class="mt-4 flex items-center gap-3" v-if="users.length">
            <button
                class="ds-btn ds-btn-primary"
                @click="sendMessage()"
                v-if="this.selectedUsersCount > 0"
            >
                Send Message ({{ this.selectedUsersCount }})
            </button>
            <button
                class="ds-btn ds-btn-secondary"
                @click="selectNone($event)"
                v-if="this.selectedUsersCount > 0"
            >
                Clear Selection
            </button>
        </div>

        <!-- Send Message Modal -->
        <div v-if="this.send == 1" class="modal modal-mask">
            <div class="modal-wrapper px-4">
                <div class="modal-container w-full max-w-md px-8 mx-auto">
                    <div class="modal-header flex justify-between items-center">
                        <h2>Send Message</h2>
                        <button
                            id="close-button"
                            class="modal-default-button text-2xl py-1"
                            @click="closeModal()"
                        >
                            &times;
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="flex flex-col">
                            <div class="w-full lg:w-1/4">
                                <label for="subject" class="tw-form-label">Subject</label>
                            </div>
                            <div class="my-2 w-full">
                                <input
                                    type="text"
                                    name="subject"
                                    v-model="subject"
                                    class="tw-form-control w-full"
                                />
                                <span
                                    v-if="errors.subject"
                                    class="text-red-500 text-xs font-semibold"
                                >{{ errors.subject[0] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="flex flex-col">
                            <div class="w-full lg:w-1/4">
                                <label for="message" class="tw-form-label">Message</label>
                            </div>
                            <div class="w-full">
                                <textarea
                                    type="text"
                                    name="message"
                                    v-model="message"
                                    class="tw-form-control w-full"
                                    rows="10"
                                ></textarea>
                                <span
                                    v-if="errors.message"
                                    class="text-red-500 text-xs font-semibold"
                                >{{ errors.message[0] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body">
                        <div class="flex items-center">
                            <div class="w-6">
                                <input
                                    type="checkbox"
                                    name="send_later"
                                    v-model="send_later"
                                    class="tw-form-control w-full"
                                    @click="enableDate($event)"
                                />
                            </div>
                            <div class="mx-1">
                                <label for="subject" class="tw-form-label">Send Later</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-body hidden" id="show_date">
                        <div class="flex">
                            <div class="w-full lg:w-1/4">
                                <label for="executed_at" class="tw-form-label">Date Time</label>
                            </div>
                            <div class="w-full lg:w-3/4">
                                <datetime
                                    format="DD-MM-YYYY h:i:s"
                                    name="executed_at"
                                    v-model="executed_at"
                                    class="w-full rounded"
                                    id="executed_at"
                                ></datetime>
                                <span
                                    v-if="errors.executed_at"
                                    class="text-red-500 text-xs font-semibold"
                                >{{ errors.executed_at[0] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="my-6">
                        <a
                            href="#"
                            class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium"
                            @click="submit()"
                        >Send</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { bus } from "../../app";
import PortalVue from "portal-vue";
import datetime from "vuejs-datetimepicker";
export default {
    props: ["url", "searchquery", "letter", "birthday"],
    data() {
        return {
            users: [],
            user: "",
            alphabets: [
                "A", "B", "C", "D", "E", "F", "G", "H", "I",
                "J", "K", "L", "M", "N", "O", "P", "Q", "R",
                "S", "T", "U", "V", "W", "X", "Y", "Z",
            ],
            selectedLetter: undefined,
            letter: "",
            active: false,
            selected: [],
            selectedUsers: [],
            selectedUsersCount: 0,
            send_later: "",
            allSelected: false,
            noneSelected: false,
            subject: "",
            message: "",
            executed_at: "",
            send: 0,
            errors: [],
            success: null,
        };
    },

    created() {
        axios.get("/admin/staffs/find?" + this.searchquery).then((response) => {
            this.users = response.data.data;
        });
        this.getUrl();
        this.letter = this.searchquery.slice(-1)[0];
    },

    computed: {
        filteredNames() {
            let users = this.users;
            if (this.selectedLetter) {
                users = users.filter((user) => {
                    let name = user['fullname'] || '';
                    let firstLetter = name.charAt(0).toUpperCase();
                    return firstLetter === this.selectedLetter;
                });
            }
            return users;
        },
    },

    components: {
        datetime,
    },

    methods: {
        clearAll() {
            window.location.href = "/admin/teachers";
        },

        enableform(val) {
            this.success = null;
            $("#show-detail").removeClass("hide-menu").addClass("block");
            bus.$emit("dataMemberName", val);
        },

        sortMembers(name) {
            this.selectedLetter = name;
            this.active = true;
            var q = "alphabet=" + this.selectedLetter;
            var url = this.currenturl;
            if (window.location.search.indexOf("alphabet=") > -1) {
                var href = new URL(url);
                href.searchParams.set("alphabet", this.selectedLetter);
                url = href.toString();
            } else {
                if (url.indexOf("?") > -1) {
                    url += "&";
                } else {
                    url += "?";
                }
                url += q;
            }
            window.location.href = url;
        },

        getUrl() {
            this.currenturl = this.url + "/admin/staffs/";
            if (this.searchquery != "") {
                this.currenturl = this.currenturl + "?" + this.searchquery;
            }
        },

        selectAll(e) {
            var selected = [];
            if (e.target.checked) {
                $(".member-list").addClass("student_selected");
                if (this.allSelected == false) {
                    this.users.forEach(function (user) {
                        selected.push(user.id);
                    });
                    this.selected = selected;
                    this.selectedUsersCount = selected.length;
                    this.allSelected = true;
                }
            } else {
                this.users.forEach(function (user) {
                    selected.splice(user.id);
                });
                this.selected = selected;
                this.selectedUsersCount = selected.length;
                this.noneSelected = false;
                $(".member-list").removeClass("student_selected");
            }
        },

        selectNone(e) {
            var selected = [];
            $(".member-list").removeClass("student_selected");
            this.users.forEach(function (user) {
                selected.splice(user.id);
            });
            this.selected = selected;
            this.selectedUsersCount = selected.length;
            this.noneSelected = false;
        },

        sendMessage() {
            if (this.selectedUsersCount > 0) {
                this.send = 1;
            } else {
                alert("Select Teachers");
            }
        },

        submit() {
            this.errors = [];
            axios
                .post("/admin/teacher/sendMessageToAll", {
                    selected: this.selected,
                    subject: this.subject,
                    message: this.message,
                    send_later: this.send_later,
                    executed_at: this.executed_at,
                })
                .then((response) => {
                    this.success = response.data.message;
                    this.send = 0;
                    window.location.reload();
                })
                .catch((error) => {
                    this.errors = error.response.data.errors;
                });
        },

        closeModal() {
            this.send = 0;
        },

        enableDate(e) {
            if (e.target.checked) {
                this.send_later = 1;
                if ($("#show_date").hasClass("hidden")) {
                    $("#show_date").removeClass("hidden").addClass("block");
                }
            } else {
                this.send_later = 0;
                if ($("#show_date").hasClass("block")) {
                    $("#show_date").removeClass("block").addClass("hidden");
                }
            }
        },

        selectedCount(id, e) {
            if (e.target.checked) {
                this.selectedUsersCount++;
                $("#" + id).addClass("student_selected");
            } else {
                this.selectedUsersCount--;
                $("#" + id).removeClass("student_selected");
            }
        },
    },
};
</script>

<style scoped>
/* Modal overlay */
.modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: table;
    transition: opacity 0.3s ease;
}

.modal-wrapper {
    display: table-cell;
    vertical-align: middle;
    overflow: auto;
}

.modal-container {
    margin: 0px auto;
    padding: 20px 30px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    transition: all 0.3s ease;
    overflow: auto;
}

.modal-header h3 {
    margin-top: 0;
    color: #42b983;
}

.modal-body {
    margin: 20px 0;
}

.modal-default-button {
    float: right;
}

.modal-enter { opacity: 0; }
.modal-leave-active { opacity: 0; }
.modal-enter .modal-container,
.modal-leave-active .modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
}

/* Name link in table */
.dt-name-link {
    color: var(--d-blue, #1E6FD9);
    text-decoration: none;
    font-weight: 500;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
}
.dt-name-link:hover {
    text-decoration: underline;
}

/* Action button in table */
.dt-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    min-height: 44px;
    color: var(--d-muted, #64748B);
    border-radius: 6px;
    transition: background 0.15s ease, color 0.15s ease;
}
.dt-action-btn:hover {
    background: #F1F5F9;
    color: var(--d-blue, #1E6FD9);
}

/* Alphabet filter active state */
.filter-alphabet .active {
    background: var(--d-blue, #1E6FD9);
    color: #fff !important;
    border-color: var(--d-blue, #1E6FD9);
}
</style>