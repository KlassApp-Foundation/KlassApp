<template>
    <div class="bg-white shadow px-4 py-3">
        <div>
            <div v-if="this.success!=null" class="alert alert-success" id="success-alert">{{this.success}}</div>
            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/3">
                            <label for="standardLink_id" class="tw-form-label">Class<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <select class="tw-form-control w-full" id="standardLink_id" v-model="standardLink_id" name="standardLink_id">
                                <option value="" disabled>Select Class</option>
                                <option v-for="standard in standardlist" v-bind:value="standard.id">{{ standard.standard_section }}</option>
                            </select>
                            <span v-if="errors.standardLink_id"><p class="text-red-500 text-xs font-semibold">{{errors.standardLink_id[0]}}</p></span>
                        </div>
                    </div> 
                </div>
            </div>

            <div class="my-5" v-if="this.standardLink_id != ''">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/3">
                            <label for="subject_id" class="tw-form-label">Subject</label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <select class="tw-form-control w-full" id="subject_id" v-model="subject_id" name="subject_id">
                                <option value="" disabled>Select Subject</option>
                                <option v-for="subject in subjectlist[this.standardLink_id]" v-bind:value="subject.subject_id">{{ subject.subject_name }}</option>
                            </select>
                            <span v-if="errors.subject_id" class="text-red-500 text-xs font-semibold">{{errors.subject_id[0]}}</span>
                        </div>
                    </div> 
                </div>
            </div> 

            <div class="my-5" v-if="this.subject_id !='' & this.standardLink_id !='' ">
                <div class="tw-form-group w-full lg:w-3/5" v-if="this.mode == 'admin'">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/3">
                            <label for="teacher_id" class="tw-form-label">Teacher</label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <select class="tw-form-control w-full" id="teacher_id" v-model="teacher_id" name="teacher_id">
                                <option value="" disabled>Select Teacher</option>
                                <option v-for="teacher in teacherlist[this.standardLink_id][this.subject_id]" v-bind:value="teacher.id">{{ teacher.fullname }}</option>
                            </select>
                            <span v-if="errors.teacher_id" class="text-red-500 text-xs font-semibold">{{errors.teacher_id[0]}}</span>
                        </div>
                    </div> 
                </div>
            </div> 
    
            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="mb-2 w-full lg:w-1/4 md:w-1/3">
                            <label for="description" class="tw-form-label">Description<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <!-- <textarea type="text" name="description" id="description" v-model="description" class="tw-form-control w-full" rows="3"></textarea> -->
                            <quill-editor ref="myQuillEditor" v-model="description" :options="editorOption"/>
                            <span v-if="errors.description" class="text-red-500 text-xs font-semibold">{{errors.description[0]}}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="w-full w-full lg:w-1/4 md:w-1/3">
                            <label for="attachment" class="tw-form-label">Attachment</label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <input type="file" name="attachment" @change="OnFileSelected" id="attachment" class="tw-form-control w-full">
                            <span>Note: Pdf or jpg only</span>
                            <span v-if="errors.attachment" class="text-red-500 text-xs font-semibold">{{errors.attachment[0]}}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="w-full w-full lg:w-1/4 md:w-1/3">
                            <label for="date" class="tw-form-label">Date<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <input type="date" name="date" v-model="date" class="tw-form-control w-full" id="date">
                            <span v-if="errors.date" class="text-red-500 text-xs font-semibold">{{errors.date[0]}}</span>
                        </div>
                    </div>
                </div>
            </div>

             <div class="my-5">
                <div class="tw-form-group w-full lg:w-3/5">
                    <div class="lg:mr-8 md:mr-8 flex flex-col lg:flex-row md:flex-row lg:items-center md:items-center w-full">
                        <div class="w-full w-full lg:w-1/4 md:w-1/3">
                            <label for="submission_date" class="tw-form-label">Submission Date<span class="text-red-500">*</span></label>
                        </div>
                        <div class="mb-2 w-full lg:w-3/4 md:w-2/3">
                            <input type="date" name="submission_date" v-model="submission_date" :min="date" class="tw-form-control w-full" id="date">
                            <span v-if="errors.submission_date" class="text-red-500 text-xs font-semibold">{{errors.submission_date[0]}}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="my-6">
                <a href="#" id="submit-btn" class="btn btn-submit blue-bg text-white rounded px-3 py-1 mr-3 text-sm font-medium" @click="checkForm()">Submit</a>
                <a href="#" class="btn btn-reset bg-gray-100 text-gray-700 border rounded px-3 py-1 mr-3 text-sm font-medium" @click="reset()">Reset</a>    
            </div>
        </div>
    </div>
</template>