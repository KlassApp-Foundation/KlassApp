<template>
    <div class="relative">
        <div v-if="this.success!=null" class="alert alert-success mt-2" id="success-alert">{{this.success}}</div>
        <portal to="add_todolist">
            <div class="flex flex-wrap lg:flex-row justify-between items-center">
                <div class="flex items-center">
                    <h1 class="admin-h1">Tasks</h1>
                     <div class="flex flex-wrap items-center mx-3">
                    <select class="tw-form-control text-xs" name="type" v-model="type" id="type" v-on:change="selectAssigned">
                        <option v-for="assigned in assignedlist" v-bind:value="assigned.id">{{ assigned.name }}</option>
                    </select>
                </div>
                </div>
               
                <div class="relative flex items-center w-8/12 lg:w-1/2 md:w-1/2 justify-end">
                    <div class="flex items-center">
                        <div class="">
                            <div class="flex lg:justify-end md:justify-end items-center">
                                <div class="search relative">
                                    <input type="text" name="search" v-model="search" class="border px-10 py-1 text-sm border-gray-400 w-full rounded bg-white shadow" placeholder="Search">  
                                    <span class="input-group-btn absolute left-0 px-3 py-2 top-0">                  
                                        <a href="#" @click="searchList()">
                                            <svg class="w-4 h-4 fill-current text-gray-600" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30.239px" height="30.239px" viewBox="0 0 30.239 30.239" style="enable-background:new 0 0 30.239 30.239;" xml:space="preserve"><g><path d="M20.194,3.46c-4.613-4.613-12.121-4.613-16.734,0c-4.612,4.614-4.612,12.121,0,16.735 c4.108,4.107,10.506,4.547,15.116,1.34c0.097,0.459,0.319,0.897,0.676,1.254l6.718,6.718c0.979,0.977,2.561,0.977,3.535,0 c0.978-0.978,0.978-2.56,0-3.535l-6.718-6.72c-0.355-0.354-0.794-0.577-1.253-0.674C24.743,13.967,24.303,7.57,20.194,3.46z M18.073,18.074c-3.444,3.444-9.049,3.444-12.492,0c-3.442-3.444-3.442-9.048,0-12.492c3.443-3.443,9.048-3.443,12.492,0 C21.517,9.026,21.517,14.63,18.073,18.074z"/></g></svg>
                                        </a>
                                    </span>
                                </div>
                                <div class="date-select date-select_none dashboard-reset mx-1 lg:mx-0 md:mx-0">
                                    <a href="#" @click="resetForm()" id="do-reset" class="text-sm border bg-gray-100 text-grey-darkest py-1 px-4">Reset</a>
                                </div>
                            </div>
                        </div>
                        <a href="#" class="no-underline text-white px-4 my-3 mx-2 flex items-center custom-green py-1 justify-center cursor-pointer rounded" @click="submitForm()" v-if="this.selectedTaskCount > 0">
                            <span class="text-sm font-semibold whitespace-no-wrap">Mark As Completed</span>
                        </a> 

                        <div class="flex items-center rounded mx-2">
                            <div v-for="list in statuslist">
                                <a href="#" class="no-underline text-black px-2 flex items-center bg-white py-1 justify-center cursor-pointer" v-bind:class="[{'blue-bg text-white' : status === list.id}]" @click="showCompletedTask(list.id)">
                                    <span class="mx-1 text-sm font-semibold">{{ list.name }}</span>
                                </a>
                            </div>
                        </div>

                        <a :href="this.url+'/'+this.mode+'/task/add'" class="no-underline text-white px-4 my-3 mx-1 flex items-center custom-green py-1 justify-center cursor-pointer">
                            <span class="mx-1 text-sm font-semibold">Add</span>
                            <svg data-v-2a22d6ae="" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 409.6 409.6" xml:space="preserve" class="w-3 h-3 fill-current text-white"><g data-v-2a22d6ae=""><g data-v-2a22d6ae=""><path data-v-2a22d6ae="" d="M392.533,187.733H221.867V17.067C221.867,7.641,214.226,0,204.8,0s-17.067,7.641-17.067,17.067v170.667H17.067 C7.641,187.733,0,195.374,0,204.8s7.641,17.067,17.067,17.067h170.667v170.667c0,9.426,7.641,17.067,17.067,17.067 s17.067-7.641,17.067-17.067V221.867h170.667c9.426,0,17.067-7.641,17.067-17.067S401.959,187.733,392.533,187.733z"></path></g></g></svg>
                        </a> 
                    </div>
                </div>
            </div>
        </portal>

        <div class="bg-white shadow my-5" v-if="Object.keys(tasks).length > 0">
            <div class="flex mx-2 my-3 items-center" v-for="(tasklist,key) in tasks">
                <div class="w-full rounded px-2 py-2 my-1">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center w-1/2 cursor-pointer" @click="showTasks(key)">
                            <svg v-if="arrow == '1_'+key" class="w-2 h-2 m-1 fill-current text-black" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="292.359px" height="292.359px" viewBox="0 0 292.359 292.359" style="enable-background:new 0 0 292.359 292.359;" xml:space="preserve"><g><path d="M222.979,133.331L95.073,5.424C91.456,1.807,87.178,0,82.226,0c-4.952,0-9.233,1.807-12.85,5.424 c-3.617,3.617-5.424,7.898-5.424,12.847v255.813c0,4.948,1.807,9.232,5.424,12.847c3.621,3.617,7.902,5.428,12.85,5.428 c4.949,0,9.23-1.811,12.847-5.428l127.906-127.907c3.614-3.613,5.428-7.897,5.428-12.847 C228.407,141.229,226.594,136.948,222.979,133.331z"/></g></svg>
                            <svg v-if="arrow != '1_'+key" class="w-2 h-2 m-1 fill-current text-black" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="292.362px" height="292.362px" viewBox="0 0 292.362 292.362" xml:space="preserve"><g><path d="M286.935,69.377c-3.614-3.617-7.898-5.424-12.848-5.424H18.274c-4.952,0-9.233,1.807-12.85,5.424 C1.807,72.998,0,77.279,0,82.228c0,4.948,1.807,9.229,5.424,12.847l127.907,127.907c3.621,3.617,7.902,5.428,12.85,5.428 s9.233-1.811,12.847-5.428L286.935,95.074c3.613-3.617,5.427-7.898,5.427-12.847C292.362,77.279,290.548,72.998,286.935,69.377z"></path></g></svg>
                            <h4 class="font-bold text-base text-gray-700 mx-1"> 
                                <span v-if="key == 0">Overdue</span>
                                <span v-else-if="key == 1">Today</span>
                                <span v-else-if="key == 2">Upcoming</span>
                            </h4>
                            <span>({{ Object.keys(tasklist).length }})</span>
                        </div>
                    </div>
                    <div>

                        <div :id="key" class="flex flex-wrap border-r border-l my-3">
                            <table class="w-full">
                                <tbody v-if="Object.keys(tasklist).length > 0">
                                    <tr class="border-b border-t bg-gray-100" v-for="list in tasklist">
                                        <td class="flex py-3 px-2 items-center">
                                            <input class="w-3 h-3 mx-2" type="checkbox" v-model="task_completed" :id="list.task_id" :value="list.task_id" @click="selectedCount(list.task_id,$event)"><label></label>

                                            <p class="font-semibold text-xs">{{ list.title }}</p>
                                        </td>
                                        <td class="py-3 px-2">
                                            <p class="font-semibold text-xs">{{ list.task_date }}</p>
                                        </td>
                                        <td class="py-3 px-2" width="5%">
                                            <div class="flex items-center">
                                                <a href="#" :title="list.reminder">
                                                    <svg class="w-4 h-4 fill-current text-black mx-1" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M347.216,301.211l-71.387-53.54V138.609c0-10.966-8.864-19.83-19.83-19.83c-10.966,0-19.83,8.864-19.83,19.83v118.978 c0,6.246,2.935,12.136,7.932,15.864l79.318,59.489c3.569,2.677,7.734,3.966,11.878,3.966c6.048,0,11.997-2.717,15.884-7.952 C357.766,320.208,355.981,307.775,347.216,301.211z"/></g></g><g><g><path d="M256,0C114.833,0,0,114.833,0,256s114.833,256,256,256s256-114.833,256-256S397.167,0,256,0z M256,472.341 c-119.275,0-216.341-97.066-216.341-216.341S136.725,39.659,256,39.659c119.295,0,216.341,97.066,216.341,216.341 S375.275,472.341,256,472.341z"/></g></g></svg>
                                                </a>
                                                <a href="#" @click="snoozeTask(list.task_id)" title="Snooze" v-if="list.snooze == 1">
                                                    <svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="512.000000pt" height="512.000000pt" viewBox="0 0 512.000000 512.000000" preserveAspectRatio="xMidYMid meet" class="w-4 h-4 fill-current text-black mx-1"><g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)" fill="#000000" stroke="none"><path d="M1860 5097 c-91 -31 -148 -67 -218 -137 -88 -89 -152 -232 -152 -340 0 -25 -7 -32 -56 -54 -351 -161 -634 -496 -744 -879 -37 -129 -47 -213 -60 -462 -10 -194 -32 -380 -65 -535 -84 -399 -269 -944 -462 -1365 -98 -212 -105 -236 -101 -335 3 -66 9 -98 30 -142 34 -73 103 -142 176 -176 l57 -27 510 -5 510 -5 3 -25 c2 -14 12 -54 23 -89 73 -233 249 -409 489 -491 98 -34 298 -40 405 -12 273 69 492 288 551 550 l16 72 490 0 c531 0 536 0 624 56 109 68 166 172 167 304 1 88 -6 111 -86 285 -265 579 -458 1194 -522 1665 -12 87 -33 401 -35 517 0 6 15 -4 34 -23 l34 -34 462 0 c320 0 468 3 483 11 39 20 57 51 57 99 0 48 -18 79 -57 99 -15 7 -126 11 -342 11 -177 0 -321 2 -321 5 0 3 162 208 360 455 353 441 360 451 360 495 0 35 -6 52 -29 77 l-29 33 -449 3 c-307 2 -460 0 -486 -8 -65 -19 -95 -95 -63 -158 26 -50 37 -52 388 -55 l328 -2 -336 -420 c-411 -513 -394 -489 -395 -534 0 -24 -6 -10 -15 43 -74 412 -358 789 -739 983 l-95 48 0 43 c0 53 -41 178 -76 234 -34 54 -118 138 -172 172 -121 76 -317 99 -452 53z m274 -217 c70 -27 138 -87 170 -152 14 -28 24 -53 22 -56 -2 -2 -40 2 -83 9 -101 16 -337 16 -431 0 -40 -7 -76 -10 -79 -7 -18 18 54 123 112 165 89 64 189 78 289 41z m73 -420 c387 -63 727 -325 885 -680 61 -139 87 -254 98 -435 20 -314 31 -430 55 -572 77 -455 261 -1018 514 -1575 77 -170 85 -210 51 -266 -11 -18 -34 -41 -52 -52 -33 -20 -54 -20 -1728 -20 -1594 0 -1697 1 -1729 18 -53 27 -76 65 -75 125 0 43 14 83 83 232 134 292 245 581 340 889 127 411 183 705 201 1046 6 107 15 236 21 285 45 374 283 712 625 887 179 92 319 126 519 127 72 1 158 -4 192 -9z m329 -3847 c-4 -16 -22 -60 -40 -99 -41 -86 -136 -185 -223 -231 -162 -87 -371 -80 -521 16 -112 72 -207 198 -236 314 l-6 27 516 0 516 0 -6 -27z"/><path d="M1853 4255 c-286 -52 -536 -233 -672 -487 -87 -161 -108 -253 -121 -528 -12 -248 -29 -389 -76 -620 -80 -395 -211 -809 -395 -1249 -44 -104 -69 -179 -69 -202 0 -78 79 -126 155 -95 41 18 64 61 167 320 166 418 287 814 362 1186 47 234 65 375 76 619 13 264 33 353 110 483 124 209 340 342 589 364 42 4 89 13 104 21 68 34 70 143 4 183 -38 24 -123 25 -234 5z"/><path d="M4509 5091 c-41 -42 -41 -107 0 -153 l29 -33 131 -5 131 -5 -160 -240 c-144 -216 -160 -244 -160 -282 0 -33 7 -50 29 -75 l29 -33 262 0 262 0 29 33 c41 46 41 111 0 153 l-29 29 -131 0 c-72 0 -131 2 -131 5 0 3 72 113 160 245 148 222 160 244 160 286 0 37 -6 51 -29 75 l-29 29 -262 0 -262 0 -29 -29z"/></g></svg>
                                                </a>

                                                <a :href="url+'/'+mode+'/task/edit/'+list.task_id" title="Edit" v-if="list.auth_id == list.created_by">
                                                    <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 477.873 477.873" xml:space="preserve" class="w-4 h-4 fill-current text-black mx-1"><g><g><path d="M392.533,238.937c-9.426,0-17.067,7.641-17.067,17.067V426.67c0,9.426-7.641,17.067-17.067,17.067H51.2 c-9.426,0-17.067-7.641-17.067-17.067V85.337c0-9.426,7.641-17.067,17.067-17.067H256c9.426,0,17.067-7.641,17.067-17.067 S265.426,34.137,256,34.137H51.2C22.923,34.137,0,57.06,0,85.337V426.67c0,28.277,22.923,51.2,51.2,51.2h307.2 c28.277,0,51.2-22.923,51.2-51.2V256.003C409.6,246.578,401.959,238.937,392.533,238.937z"></path></g></g> <g><g><path d="M458.742,19.142c-12.254-12.256-28.875-19.14-46.206-19.138c-17.341-0.05-33.979,6.846-46.199,19.149L141.534,243.937 c-1.865,1.879-3.272,4.163-4.113,6.673l-34.133,102.4c-2.979,8.943,1.856,18.607,10.799,21.585 c1.735,0.578,3.552,0.873,5.38,0.875c1.832-0.003,3.653-0.297,5.393-0.87l102.4-34.133c2.515-0.84,4.8-2.254,6.673-4.13 l224.802-224.802C484.25,86.023,484.253,44.657,458.742,19.142z M434.603,87.419L212.736,309.286l-66.287,22.135l22.067-66.202 L390.468,43.353c12.202-12.178,31.967-12.158,44.145,0.044c5.817,5.829,9.095,13.72,9.12,21.955 C443.754,73.631,440.467,81.575,434.603,87.419z"></path></g></g></svg>
                                                </a>

                                                <a href="#" @click="showModal(list.task_id)" title="View">
                                                    <svg class="w-4 h-4 fill-current text-black mx-1" height="512pt" viewBox="-27 0 512 512" width="512pt" xmlns="http://www.w3.org/2000/svg"><path d="m188 492c0 11.046875-8.953125 20-20 20h-88c-44.113281 0-80-35.886719-80-80v-352c0-44.113281 35.886719-80 80-80h245.890625c44.109375 0 80 35.886719 80 80v191c0 11.046875-8.957031 20-20 20-11.046875 0-20-8.953125-20-20v-191c0-22.054688-17.945313-40-40-40h-245.890625c-22.054688 0-40 17.945312-40 40v352c0 22.054688 17.945312 40 40 40h88c11.046875 0 20 8.953125 20 20zm117.890625-372h-206c-11.046875 0-20 8.953125-20 20s8.953125 20 20 20h206c11.042969 0 20-8.953125 20-20s-8.957031-20-20-20zm20 100c0-11.046875-8.957031-20-20-20h-206c-11.046875 0-20 8.953125-20 20s8.953125 20 20 20h206c11.042969 0 20-8.953125 20-20zm-226 60c-11.046875 0-20 8.953125-20 20s8.953125 20 20 20h105.109375c11.046875 0 20-8.953125 20-20s-8.953125-20-20-20zm355.472656 146.496094c-.703125 1.003906-3.113281 4.414062-4.609375 6.300781-6.699218 8.425781-22.378906 28.148437-44.195312 45.558594-27.972656 22.324219-56.757813 33.644531-85.558594 33.644531s-57.585938-11.320312-85.558594-33.644531c-21.816406-17.410157-37.496094-37.136719-44.191406-45.558594-1.5-1.886719-3.910156-5.300781-4.613281-6.300781-4.847657-6.898438-4.847657-16.097656 0-22.996094.703125-1 3.113281-4.414062 4.613281-6.300781 6.695312-8.421875 22.375-28.144531 44.191406-45.554688 27.972656-22.324219 56.757813-33.644531 85.558594-33.644531s57.585938 11.320312 85.558594 33.644531c21.816406 17.410157 37.496094 37.136719 44.191406 45.558594 1.5 1.886719 3.910156 5.300781 4.613281 6.300781 4.847657 6.898438 4.847657 16.09375 0 22.992188zm-41.71875-11.496094c-31.800781-37.832031-62.9375-57-92.644531-57-29.703125 0-60.84375 19.164062-92.644531 57 31.800781 37.832031 62.9375 57 92.644531 57s60.84375-19.164062 92.644531-57zm-91.644531-38c-20.988281 0-38 17.011719-38 38s17.011719 38 38 38 38-17.011719 38-38-17.011719-38-38-38zm0 0"/></svg>
                                                </a>

                                                <a href="#" @click="deleteTask(list.task_id)" title="Delete" v-if="list.auth_id == list.created_by">
                                                    <svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" xml:space="preserve" class="w-4 h-4 fill-current text-black mx-1"><g><g><g><polygon points="353.574,176.526 313.496,175.056 304.807,412.34 344.885,413.804"></polygon><rect x="235.948" y="175.791" width="40.104" height="237.285"></rect><polygon points="207.186,412.334 198.497,175.049 158.419,176.52 167.109,413.804"></polygon><path d="M17.379,76.867v40.104h41.789L92.32,493.706C93.229,504.059,101.899,512,112.292,512h286.74 c10.394,0,19.07-7.947,19.972-18.301l33.153-376.728h42.464V76.867H17.379z M380.665,471.896H130.654L99.426,116.971h312.474 L380.665,471.896z"></path></g></g></g> <g><g><path d="M321.504,0H190.496c-18.428,0-33.42,14.992-33.42,33.42v63.499h40.104V40.104h117.64v56.815h40.104V33.42 C354.924,14.992,339.932,0,321.504,0z"></path></g></g></svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr class="border-b">
                                        <td colspan="4">
                                            <p class="font-semibold text-s" style="text-align: center">No Records Found</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                        <div v-for="list in tasklist">
                            <div v-if="show == list.task_id+'_show'" class="modal modal-mask">
                                <div class="modal-wrapper px-4">
                                    <div class="modal-container w-full max-w-md px-4 mx-auto">
                                        <div class="modal-header flex justify-between items-center">
                                            <h2>View Task</h2>
                                            <button id="close-button" class="modal-default-button text-2xl py-1" @click="closeModal()">&times;</button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="flex">
                                                <div class="w-full lg:w-1/4">
                                                    <label for="title" class="tw-form-label">Title</label>
                                                </div>
                                                <div class="w-full lg:w-3/4">
                                                    <p class="tw-form-control w-full">{{ task.title }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <div class="flex">
                                                <div class="w-full lg:w-1/4">
                                                    <label for="description" class="tw-form-label">Description</label>
                                                </div>
                                                <div class="w-full lg:w-3/4">
                                                    <p class="tw-form-control w-full" v-html="task.to_do_list"></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <div class="flex">
                                                <div class="w-full lg:w-1/4">
                                                    <label for="assignee" class="tw-form-label">Assignee Type</label>
                                                </div>
                                                <div class="w-full lg:w-3/4">
                                                    <p class="tw-form-control w-full">{{ task.assignee_display }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <div class="flex">
                                                <div class="w-full lg:w-1/4">
                                                    <label for="assignee" class="tw-form-label">Assigned To</label>
                                                </div>
                                                <div class="w-full lg:w-3/4" v-if="task.assignee == 'teacher'">
                                                    <p class="tw-form-control w-full" v-for="teacher in task.teachers">
                                                        <span v-if="mode == 'admin'">
                                                            <a :href="url+'/'+mode+'/teacher/show/'+teacher.name"> {{ teacher.fullname }}</a>
                                                        </span>
                                                        <span v-else>{{ teacher.fullname }}</span>
                                                    </p>
                                                </div>
                                                <div class="w-full lg:w-3/4" v-else-if="task.assignee == 'class'">
                                                    <p class="tw-form-control w-full">{{ task.class }}</p>
                                                </div>
                                                <div class="w-full lg:w-3/4" v-else-if="task.assignee == 'student'">
                                                    <p class="tw-form-control w-full" v-for="student in task.selectedUsers">
                                                        <span v-if="mode == 'admin'">
                                                            <a :href="url+'/'+mode+'/student/show/'+student.name"> {{ student.fullname }}</a>
                                                        </span>
                                                        <span v-else>{{ student.fullname }}</span>
                                                    </p>
                                                </div>
                                                <div class="w-full lg:w-3/4" v-else>
                                                    <p class="tw-form-control w-full">{{ task.assignee_display }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <div class="flex">
                                                <div class="w-full lg:w-1/4">
                                                    <label for="task_date" class="tw-form-label">Due Date</label>
                                                </div>
                                                <div class="w-full lg:w-3/4">
                                                    <p class="tw-form-control w-full">{{ task.task_date }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-body">
                                            <div class="flex">
                                                <div class="w-full lg:w-1/4">
                                                    <label for="reminder_date" class="tw-form-label">Reminder On</label>
                                                </div>
                                                <div class="w-full lg:w-3/4">
                                                    <p class="tw-form-control w-full">{{ task.reminder_date }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
        <div class="bg-white shadow my-5 py-2 text-sm" v-else>
            <p class="text-center">No Records Found</p>
        </div>
    </div>
</template>

<script>
    import { bus } from "../../app";
    export default {
        props:['url' , 'mode' , 'hidecolumns'],
        data () {
            return {
                tasks:[],
                task:[],
                task_completed:[],
                type:'by_me',
                status:0,
                showCompleted:'',
                selectedTaskCount:0,
                params:{},
                arrow:0,
                show:'',
                search:'',
                assignedlist:[ { id : 'by_me' , name : 'Assigned By Me' } , { id : 'to_me' , name : 'Assigned To Me' } ],
                statuslist:[ { id : 0 , name : 'Active' } , { id : 1 , name : 'Completed' } ],
                errors:[],
                success:null,
            }
        },

        methods:
        {
            getlist()
            {
                axios.get('/'+this.mode+'/task/list?status='+this.status+'&type='+this.type+'&search='+this.search).then(response => {
                    this.tasks = response.data;
                    //console.log(this.tasks);    
                });
            },

            showModal(id)
            {
                this.show = id+'_show';
                axios.get('/'+this.mode+'/task/show/'+id).then(response => {
                    this.task = response.data;
                    //console.log(this.task);    
                });
            },

            closeModal()
            {
                this.show = 0;
            },

            searchList()
            {
                this.getlist();
            },

            resetForm()
            {
                this.search = '';
                this.getlist();
            },

            showTasks(key)
            {
                if( $('#'+key).hasClass('hidden') )
                {
                    $('#'+key).removeClass('hidden').addClass('block');
                    this.arrow = '0_'+key;
                }
                else
                {
                    $('#'+key).removeClass('block').addClass('hidden');
                    this.arrow = '1_'+key;
                }
            },

            selectAssigned()
            {
                this.final = this.url+'/'+this.mode+'/task/list?status='+this.status+'&type='+this.type;

                axios.get(this.final).then(response => {
                    this.tasks = response.data;
                });
            },

            showCompletedTask(e)
            {
                this.status = e;

                this.final = this.url+'/'+this.mode+'/task/list?status='+this.status+'&type='+this.type;

                axios.get(this.final).then(response => {
                    this.tasks = response.data;
                });
            },

            selectedCount(id,e) 
            { 
                if (e.target.checked) 
                {
                    this.selectedTaskCount++;
                    this.task_completed.push(id);
                    $('#'+id).addClass('student_selected');
                }
                else
                {
                    this.selectedTaskCount--;
                    this.task_completed.splice(id);
                    $('#'+id).removeClass('student_selected');
                }
            },

            snoozeTask(id)
            {     
                this.errors=[];
                this.success=null;
                
                axios.post('/'+this.mode+'/task/snooze/'+id).then(response => {
                    this.success = response.data.success;
                    //window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            submitForm()
            {     
                this.errors=[];
                this.success=null;
                
                axios.post('/'+this.mode+'/task/completed',{
                    task_completed:this.task_completed, 
                    selectedTaskCount:this.selectedTaskCount,
                }).then(response => {
                    this.success = response.data.success;
                    window.location.reload();
                }).catch(error => {
                    this.errors = error.response.data.errors;
                });
            },

            deleteTask(id) 
            {
                var thisswal = this;
                swal({
                    title: 'Are you sure',
                    text: 'Do you want to delete this task ?',
                    icon: "info",
                    buttons: [
                        'No',
                        'Yes'
                    ],
                    dangerMode: true,
                }).then(function(isConfirm) {
                    if (isConfirm) 
                    {
                        axios.get('/'+thisswal.mode+'/task/'+id+'/delete').then(response => {
                            thisswal.success    = response.data.success;
                            thisswal.getlist();
                        });  
                    }
                    else 
                    {
                        swal("Cancelled");
                    }
                });
            },
        },
  
        created()
        {   
            this.getlist();
        }
    }
</script>

<style scoped>
    .modal-mask {
        position: fixed;
        z-index: 9998;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, .5);
        display: table;
        transition: opacity .3s ease;
    }

    .modal-wrapper {
        display: table-cell;
        vertical-align: middle;
        overflow:auto;
    }

    .modal-container-new {
        margin: 0px auto;
        /*padding: 20px 30px;*/
        background-color: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
        transition: all .3s ease;
        height: 500px;
        overflow:auto;
    }

    .modal-container {
        margin: 0px auto;
        /*padding: 20px 30px;*/
        background-color: #fff;
        border-radius: 2px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .33);
        transition: all .3s ease;
        /*height: 500px;*/
        overflow:auto;
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

    /*
     * The following styles are auto-applied to elements with
     * transition="modal" when their visibility is toggled
     * by Vue.js.
     *
     * You can easily play with the modal transition by editing
     * these styles.
     */
    .modal-enter {
        opacity: 0;
    }

    .modal-leave-active {
        opacity: 0;
    }

    .modal-enter .modal-container,
    .modal-leave-active .modal-container {
        -webkit-transform: scale(1.1);
        transform: scale(1.1);
    }
</style>