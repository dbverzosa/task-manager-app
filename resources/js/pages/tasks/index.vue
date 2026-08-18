<script setup lang ="ts">

import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { type BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { Trash2, CheckCircle2, Circle, Search, X, Plus, Pencil, Loader2 } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { watchDebounced } from '@vueuse/core';

interface Task {
    id:number;
    title: string;
    description?: string;
    priority: 'low' | 'normal' | 'high';
    completed: boolean;
    created_at: string;
    list: {
        id: number;
        name: string;
        color?: string;
    };

    list_id: number;
}

interface TodoList {
    id: number;
    name: string;
    color?: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedTasks {
    data: Task[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}


const props = defineProps < {
    tasks: PaginatedTasks;
    lists: TodoList[];
    filters: {
        search?:  string;
        priority?: string;
        list_id?: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {title: 'Dashboard', href: dashboard().url},
    {title: 'All Tasks', href: '/tasks'},
];


//Filter State
const search = ref(props.filters.search || '');
const priority = ref(props.filters.priority || '');
const listId = ref(props.filters.list_id || '');


//Dialog State
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingTask = ref<Task | null>(null);
const deletingTaskId = ref<number |null>(null);

const createForm = useForm ({
    title: '',
    description: '',
    list_id: props.filters.list_id || '',
    priority: 'normal',
});

const editForm = useForm ({
    title: '',
    description: '',
    priority: 'normal',
});

//Watch for filter changes and update URL with debounce
watchDebounced([search,priority, listId], () => {
    router.get('/tasks', {
        search: search.value || undefined,
        priority: priority.value || undefined,
        list_id: listId.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, {debounce: 300});


const clearFilters = () => {
    search.value = '';
    priority.value = '';
    listId.value = '';
};


const toggleTaskCompletion = (task: Task) => {
    router.put(`/tasks/${task.id}`, {
        title: task.title,
        description: task.description,
        priority: task.priority,
        completed: !task.completed,
    }, {
        preserveScroll: true,
    });
};

const createTask = () => {
    createForm.post('/lists/tasks', {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        },
    });
};

const updateTask = () => {
    if (!editingTask.value) return;

    editForm.put(`/tasks/${editingTask.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editForm.reset();
        },
    });
};


const deleteTask = (taskId: number) => {
    if(confirm('Are you sure you want to delete this task?')) {
        deletingTaskId.value=taskId;
        router.delete( `/tasks/${taskId}`, {
            preserveScroll: true,
            onFinish: () => {
                deletingTaskId.value = null;
            },
        });
    }
};

const openEditDialog = (task: Task) => {
    editingTask.value = { ...task};
    editForm.title = task.title;
    editForm.description = task.description || '';
    editForm.priority = task.priority;
    isEditDialogOpen.value = true;
};

const getPriorityVariant = (priority: string): 'default' | 'secondary' | 'destructive' => {
    switch(priority) { 
        case 'high':
            return 'destructive';
        case 'low':
            return 'secondary';
        default: 
            return 'default';
    }
};
</script>


<template>
    <Head title="All Tasks"/>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold"> All Tasks</h1>
                    <p class="text-muted-foreground"> View and manage all your  tasks ({{ tasks.total }} total)</p>
                </div>

                <!-- Create Task Dialog-->
                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogTrigger as-child>
                        <Button>
                            <Plus class="h-4 w-4 mr-2"/>
                            Add Task
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle> Add New Task</DialogTitle>
                            <DialogDescription> Create a new task and assign it to a list.</DialogDescription>
                        </DialogHeader>

                        <form @submit.prevent="createTask" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="title"> Task title</Label>
                                <Input id="title" 
                                v-model="createForm.title"
                                required
                                placeholder="Enter task title"/>
                                <InputError :message="createForm.errors?.title"/>
                            </div>
                            <div class="space-y-4">
                                <Label for="list_id">List</Label>
                                <select 
                                    id="list_id"
                                    v-model="createForm.list_id"
                                    required
                                    class="flex h-10 w-full rounder-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring"
                                >
                                <option value="" disabled> Select a list</option>
                                <option v-for="list in lists" :key="list.id" :value="list.id"> {{ list.name }}</option>
                                </select>
                                <InputError :message="createForm.errors?.list_id"/>
                            </div>
                            <div class="space-y-2">
                                <Label for="description"> Description</Label>
                                <textarea 
                                    id="description" 
                                    v-model="createForm.description" 
                                    placeholder="Add description..." 
                                    rows="3"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                />
                                <InputError :message="createForm.errors?. description"/>
                            </div>
                            <div class="space-y-2">
                                <Label for="priority"> Priority</Label>
                                <select id="priority " 
                                v-model="createForm.priority" 
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                </select>
                                <InputError :message="createForm.errors?.priority"/>
                            </div>
                            <Button type="submit" class="w-full" :disabled="createForm.processing">
                                <Loader2 v-if="createForm.processing" class="h-4 w-4 mr-2 animate-spin"/>
                                {{ createForm.processing ? 'Creating...' : 'Create Task' }}
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>

                <!-- Edit Task Dialog-->

                <Dialog v-model:open="isEditDialogOpen">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle> Edit Task </DialogTitle>
                            <DialogDescription> Update the task details.</DialogDescription>
                        </DialogHeader>
                        <form v-if="editingTask" @submit.prevent="updateTask" class="space-y-4">
                            <div class="space-y-2">
                                <Label for="edit-title">Task Title</Label>
                                <Input id="edit-title" v-model="editForm.title" required placeholder="Enter task title"/>
                                <InputError :message="editForm.errors?.title"/>
                            </div>
                            <div class="space-y-2">
                                <Label for="edit-description"> Description</Label>
                                <textarea 
                                    id="edit-description" 
                                    v-model="editForm.description" 
                                    placeholder="Add description..." 
                                    rows="3" 
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                />
                                <InputError :message="editForm.errors?.description"/>
                            </div>
                            <div class="space-y-2">
                                <Label for="edit-priority"> Priority </Label>
                                <select id="edit-priority" v-model="editForm.priority" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                                    <option value="low">Low</option>
                                    <option value="normal">Normal</option>
                                    <option value="high">High</option>
                                </select>
                               <InputError :message="editForm.errors?.priority"/>
                            </div>
                            <Button type="submit" class="w-full" :disabled="editForm.processing">
                                <Loader2 v-if="editForm.processing" class="h-4 w-4 mr-2 animate-spin"/>
                                {{ editForm.processing ? 'Updating...' : 'Update Task' }}
                            </Button>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

              <!-- Filters-->

                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle> Filters </CardTitle>
                            <Button variant="ghost" size="sm" @click="clearFilters">
                                <X class="h-4 w-4 mr-2"/>
                                Clear Filters
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-3">
                            <div class="space-y-2">
                                <Label for="search">
                                    Search
                                </Label>
                                <div class="relative">
                                    <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground"/>
                                <Input
                                    id="search"
                                    v-model="search"
                                    placeholder="Search tasks..."
                                    class="pl-8"
                                />
                                </div>
                            </div>
                            <div class="space-y-2">
                                <Label for="list"> List </Label>
                                <select
                                id="list"
                                v-model="listId"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                <option value=""> All Lists </option>
                                <option v-for="list in lists" :key="list.id" :value="list.id">
                                    {{ list.name }}
                                </option>
                                 </select>
                            </div>

                            <div class="space-y-2">
                            <Label for="priority"> Priority </Label>
                            <select id="priority" v-model="priority" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">  
                                <option value=""> All Priorities </option>
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                    </CardContent>      
                </Card>

                <!--Task Table-->
                <Card>
                    <CardHeader>
                        <CardTitle> Tasks ({{  tasks.data.length }} of {{ tasks.total }})</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="tasks.data.length > 0" class="space-y-4">
                            <div class="rounded-md border">
                                <table class="w-full caption-bottom text-sm">
                                    <thead class="[&_tr]:border-b">
                                        <tr class="border-b transition-colors hover:bg-muted/50">
                                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"> Title  </th>
                                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"> Description </th>
                                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-[150px]"> List </th>
                                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-[100px]"> Priority </th>
                                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground w-[100px]"> Actions </th>
                                        </tr>
                                    </thead>
                                    <tbody class="[&_tr: last-child]: border-0">
                                        <tr 
                                            v-for="task in tasks.data"
                                            :key="task.id"
                                            class="border-b transition-colors hover:bg-muted/50"
                                        >
                                        <td class="p-4 align-middle">
                                            <div class="flex items-center gap-3">
                                                <button
                                                    @click="toggleTaskCompletion(task)"
                                                    class="flex items-center justify-center shrink-0"
                                                >
                                                <CheckCircle2 v-if="task.completed" class=" h-5 w-5 text-green-600"/>
                                                <Circle v-else class="h-5 w-5 text-muted-foreground"/>
                                                </button>
                                                <span :class="{'line-through text-muted-foreground': task.completed }">
                                                     {{ task.title }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <span class="text-sm text-muted-foreground" :class="{'line-through': task.completed}"> {{ task.description || '-' }}</span>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="flex items-center gap-2">
                                                <div class="w-3 h-3 rounded-full" :style="{backgroundColor: task.list.color || '#6366f1'}"/>
                                                <span class="text-sm"> {{ task.list.name }}</span>
                                            </div>
                                        </td>
                                    
                                        <td class="p-4 align-middle">
                                            <Badge :variant="getPriorityVariant(task.priority)">
                                                {{ task.priority }}
                                            </Badge>
                                        </td>
                                        <td class="p-4 align-middle">
                                            <div class="flex items-center gap-2">
                                                <Button variant="ghost" size="sm" @click="openEditDialog(task)">
                                                    <Pencil class="h-4 w-4"/>
                                                </Button>
                                                <Button variant="ghost" size="sm" @click="deleteTask(task.id)" :disabled="deletingTaskId === task.id">
                                                    <Loader2 v-if="deletingTaskId === task.id" class="h-4 w-4 animate-spin"/>
                                                    <Trash2 v-else class="h-4 w-4"/>
                                                </Button>
                                            </div>
                                        </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!--Pagination-->

                            <div class="flex items-center justify-between">
                                <p class="text-sm text-muted-foreground">
                                    Showing {{  tasks.data.length }} of {{ tasks.total }} tasks
                                </p>
                                <div class="flex items-center gap-2">
                                    <Link 
                                        v-for="link in tasks.links"
                                        :key="link.label"
                                        :href="link.url || '#' "
                                        :class="[
                                                'px-3 py-1 text-sm rounded-md',
                                                link.active
                                                ? 'bg-primary text-primary-foreground'
                                                : link.url
                                                ? 'hover:bg-muted'
                                                : 'opacity-50 cursor-not-allowed'
                                                ]"
                                        :preserve-state="true"
                                        :preserve-scroll="true"
                                        v-html="link.label"
                                    />
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12 text-muted-foreground">
                            No tasks found. Try adjusting your filters.
                        </div>
                    </CardContent>
                </Card>
        </div>
    </AppLayout>
</template>