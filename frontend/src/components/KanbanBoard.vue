<template>
  <div class="kanban-board">
    <div class="kanban-header">
      <h2>Tableau Kanban</h2>
      <button 
        v-if="canManageProject" 
        @click="showAddColumnModal = true" 
        class="add-column-btn"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
        </svg>
        Ajouter une colonne
      </button>
    </div>

    <div 
      class="kanban-columns" 
      ref="kanbanContainer"
      @dragover.prevent
      @drop="handleColumnDrop"
    >
      <div
        v-for="(column, index) in columns"
        :key="column.id"
        class="kanban-column"
        :style="{ '--column-color': column.color }"
        :draggable="canManageProject"
        @dragstart="handleColumnDragStart($event, index)"
        @dragend="handleColumnDragEnd"
        @dragover.prevent
        @drop="handleTaskDrop($event, column)"
      >
        <div class="column-header">
          <div class="column-info">
            <h3 class="column-title">{{ column.name }}</h3>
            <span class="task-count">{{ column.tasksCount }}</span>
          </div>
          <div class="column-actions">
            <button 
              v-if="canManageProject && !column.isDefault"
              @click="deleteColumn(column.id)"
              class="delete-column-btn"
              title="Supprimer la colonne"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
              </svg>
            </button>
            <button 
              v-if="canManageProject"
              @click="editColumn(column)"
              class="edit-column-btn"
              title="Modifier la colonne"
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
              </svg>
            </button>
          </div>
        </div>

        <div class="column-content">
          <div
            v-for="(task, taskIndex) in column.tasks"
            :key="task.id"
            class="task-card"
            :draggable="true"
            @dragstart="handleTaskDragStart($event, task, column.id, taskIndex)"
            @dragend="handleTaskDragEnd"
            @click="editTask(task)"
          >
            <div class="task-header">
              <h4 class="task-title">{{ task.title }}</h4>
              <span class="task-priority" :class="`priority-${task.priority}`">
                {{ getPriorityLabel(task.priority) }}
              </span>
            </div>
            
            <p v-if="task.description" class="task-description">
              {{ task.description }}
            </p>
            
            <div class="task-footer">
              <div v-if="task.assignee" class="task-assignee">
                <div class="assignee-avatar">
                  {{ getInitials(task.assignee.firstName, task.assignee.lastName) }}
                </div>
                <span class="assignee-name">
                  {{ task.assignee.firstName }} {{ task.assignee.lastName }}
                </span>
              </div>
              
              <div v-if="task.dueDate" class="task-due-date">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                </svg>
                {{ formatDate(task.dueDate) }}
              </div>
            </div>
          </div>

          <button 
            @click="addTask(column.id)"
            class="add-task-btn"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
              <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            Ajouter une carte
          </button>
        </div>
      </div>
    </div>

    <!-- Modal pour ajouter une colonne -->
    <div v-if="showAddColumnModal" class="modal-overlay" @click="showAddColumnModal = false">
      <div class="modal-content" @click.stop>
        <h3>Ajouter une colonne</h3>
        <form @submit.prevent="createColumn">
          <div class="form-group">
            <label for="columnName">Nom de la colonne</label>
            <input
              id="columnName"
              v-model="newColumn.name"
              type="text"
              required
              placeholder="Ex: En révision"
            />
          </div>
          
          <div class="form-group">
            <label for="columnDescription">Description (optionnel)</label>
            <textarea
              id="columnDescription"
              v-model="newColumn.description"
              placeholder="Description de la colonne..."
            ></textarea>
          </div>
          
          <div class="form-group">
            <label for="columnColor">Couleur</label>
            <input
              id="columnColor"
              v-model="newColumn.color"
              type="color"
            />
          </div>
          
          <div class="modal-actions">
            <button type="button" @click="showAddColumnModal = false" class="btn-secondary">
              Annuler
            </button>
            <button type="submit" class="btn-primary">
              Créer la colonne
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal pour modifier une colonne -->
    <div v-if="showEditColumnModal" class="modal-overlay" @click="showEditColumnModal = false">
      <div class="modal-content" @click.stop>
        <h3>Modifier la colonne</h3>
        <form @submit.prevent="updateColumn">
          <div class="form-group">
            <label for="editColumnName">Nom de la colonne</label>
            <input
              id="editColumnName"
              v-model="editingColumn.name"
              type="text"
              required
            />
          </div>
          
          <div class="form-group">
            <label for="editColumnDescription">Description (optionnel)</label>
            <textarea
              id="editColumnDescription"
              v-model="editingColumn.description"
            ></textarea>
          </div>
          
          <div class="form-group">
            <label for="editColumnColor">Couleur</label>
            <input
              id="editColumnColor"
              v-model="editingColumn.color"
              type="color"
            />
          </div>
          
          <div class="modal-actions">
            <button type="button" @click="showEditColumnModal = false" class="btn-secondary">
              Annuler
            </button>
            <button type="submit" class="btn-primary">
              Modifier la colonne
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { columnService, type Column, type Task } from '@/services/columnService'
import { taskService } from '@/services/taskService'

interface Props {
  projectId: number
  canManageProject?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  canManageProject: false
})

const emit = defineEmits<{
  taskUpdated: []
  columnUpdated: []
  addTask: [columnId: number]
  editTask: [task: Task]
}>()

// État réactif
const columns = ref<Column[]>([])
const loading = ref(false)
const showAddColumnModal = ref(false)
const showEditColumnModal = ref(false)
const editingColumn = ref<Column | null>(null)

// Données pour les formulaires
const newColumn = ref({
  name: '',
  description: '',
  color: '#6B7280'
})

// État du drag & drop
const draggedColumnIndex = ref<number | null>(null)
const draggedTask = ref<{ task: Task; sourceColumnId: number; sourceIndex: number } | null>(null)

// Charger les colonnes
const loadColumns = async () => {
  try {
    loading.value = true
    columns.value = await columnService.getColumnsByProject(props.projectId)
  } catch (error) {
    console.error('Erreur lors du chargement des colonnes:', error)
  } finally {
    loading.value = false
  }
}

// Créer une colonne
const createColumn = async () => {
  try {
    const newCol = await columnService.createColumn(props.projectId, newColumn.value)
    columns.value.push(newCol)
    showAddColumnModal.value = false
    newColumn.value = { name: '', description: '', color: '#6B7280' }
    emit('columnUpdated')
  } catch (error) {
    console.error('Erreur lors de la création de la colonne:', error)
  }
}

// Modifier une colonne
const editColumn = (column: Column) => {
  editingColumn.value = { ...column }
  showEditColumnModal.value = true
}

const updateColumn = async () => {
  if (!editingColumn.value) return
  
  try {
    const updated = await columnService.updateColumn(editingColumn.value.id, {
      name: editingColumn.value.name,
      description: editingColumn.value.description,
      color: editingColumn.value.color
    })
    
    const index = columns.value.findIndex(c => c.id === updated.id)
    if (index !== -1) {
      columns.value[index] = updated
    }
    
    showEditColumnModal.value = false
    editingColumn.value = null
    emit('columnUpdated')
  } catch (error) {
    console.error('Erreur lors de la mise à jour de la colonne:', error)
  }
}

// Supprimer une colonne
const deleteColumn = async (columnId: number) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer cette colonne ?')) return
  
  try {
    await columnService.deleteColumn(columnId)
    columns.value = columns.value.filter(c => c.id !== columnId)
    emit('columnUpdated')
  } catch (error) {
    console.error('Erreur lors de la suppression de la colonne:', error)
  }
}

// Drag & Drop pour les colonnes
const handleColumnDragStart = (event: DragEvent, index: number) => {
  if (!props.canManageProject) return
  draggedColumnIndex.value = index
  event.dataTransfer!.effectAllowed = 'move'
}

const handleColumnDragEnd = () => {
  draggedColumnIndex.value = null
}

const handleColumnDrop = async (event: DragEvent) => {
  if (!props.canManageProject || draggedColumnIndex.value === null) return
  
  const targetIndex = getDropIndex(event)
  if (targetIndex === null || targetIndex === draggedColumnIndex.value) return
  
  // Réorganiser les colonnes
  const draggedColumn = columns.value[draggedColumnIndex.value]
  columns.value.splice(draggedColumnIndex.value, 1)
  columns.value.splice(targetIndex, 0, draggedColumn)
  
  // Mettre à jour les positions
  const reorderData = columns.value.map((col, index) => ({
    id: col.id,
    position: index + 1
  }))
  
  try {
    await columnService.reorderColumns(reorderData)
    emit('columnUpdated')
  } catch (error) {
    console.error('Erreur lors de la réorganisation des colonnes:', error)
    // Recharger en cas d'erreur
    loadColumns()
  }
}

// Drag & Drop pour les tâches
const handleTaskDragStart = (event: DragEvent, task: Task, columnId: number, taskIndex: number) => {
  draggedTask.value = { task, sourceColumnId: columnId, sourceIndex: taskIndex }
  event.dataTransfer!.effectAllowed = 'move'
}

const handleTaskDragEnd = () => {
  draggedTask.value = null
}

const handleTaskDrop = async (event: DragEvent, targetColumn: Column) => {
  if (!draggedTask.value) return
  
  const { task, sourceColumnId, sourceIndex } = draggedTask.value
  
  // Si c'est la même colonne, on peut réorganiser
  if (sourceColumnId === targetColumn.id) {
    // Logique de réorganisation dans la même colonne
    return
  }
  
  // Déplacer vers une autre colonne
  try {
    // Mettre à jour la tâche côté backend
    await taskService.updateTask(task.id, {
      columnId: targetColumn.id,
      position: targetColumn.tasks.length
    })
    
    // Mettre à jour l'état local
    const sourceColumn = columns.value.find(c => c.id === sourceColumnId)
    if (sourceColumn) {
      sourceColumn.tasks.splice(sourceIndex, 1)
      sourceColumn.tasksCount--
    }
    
    targetColumn.tasks.push(task)
    targetColumn.tasksCount++
    
    emit('taskUpdated')
  } catch (error) {
    console.error('Erreur lors du déplacement de la tâche:', error)
    // Recharger en cas d'erreur
    loadColumns()
  }
}

// Utilitaires
const getDropIndex = (event: DragEvent): number | null => {
  const container = event.currentTarget as HTMLElement
  const columns = container.querySelectorAll('.kanban-column')
  
  for (let i = 0; i < columns.length; i++) {
    const rect = columns[i].getBoundingClientRect()
    if (event.clientX < rect.left + rect.width / 2) {
      return i
    }
  }
  
  return columns.length - 1
}

const getPriorityLabel = (priority: string): string => {
  const labels: Record<string, string> = {
    'low': 'Faible',
    'medium': 'Moyenne',
    'high': 'Élevée',
    'urgent': 'Urgente'
  }
  return labels[priority] || priority
}

const getInitials = (firstName: string, lastName: string): string => {
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString('fr-FR')
}

const addTask = (columnId: number) => {
  // Émettre un événement pour ouvrir le modal de création de tâche
  emit('addTask', columnId)
}

const editTask = (task: Task) => {
  // Émettre un événement pour ouvrir le modal d'édition de tâche
  emit('editTask', task)
}

// Exposer la méthode loadColumns pour le parent
defineExpose({
  loadColumns
})

// Lifecycle
onMounted(() => {
  loadColumns()
})
</script>

<style scoped>
.kanban-board {
  padding: 20px;
  background: #f8fafc;
  min-height: 100vh;
}

.kanban-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.kanban-header h2 {
  margin: 0;
  color: #1e293b;
  font-size: 24px;
  font-weight: 600;
}

.add-column-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #3b82f6;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.add-column-btn:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.kanban-columns {
  display: flex;
  gap: 20px;
  overflow-x: auto;
  padding-bottom: 20px;
}

.kanban-column {
  min-width: 300px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border-top: 4px solid var(--column-color, #6b7280);
  transition: all 0.2s;
}

.kanban-column:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.kanban-column[draggable="true"] {
  cursor: move;
}

.column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.column-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.column-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.task-count {
  background: #e2e8f0;
  color: #64748b;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
}

.column-actions {
  display: flex;
  gap: 8px;
}

.delete-column-btn,
.edit-column-btn {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: all 0.2s;
}

.delete-column-btn:hover {
  color: #ef4444;
  background: #fef2f2;
}

.edit-column-btn:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.column-content {
  padding: 16px 20px;
  min-height: 200px;
}

.task-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.task-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
  transform: translateY(-1px);
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.task-title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
  line-height: 1.4;
}

.task-priority {
  font-size: 10px;
  font-weight: 600;
  padding: 2px 6px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.priority-low {
  background: #dcfce7;
  color: #166534;
}

.priority-medium {
  background: #fef3c7;
  color: #92400e;
}

.priority-high {
  background: #fed7d7;
  color: #991b1b;
}

.priority-urgent {
  background: #fecaca;
  color: #7f1d1d;
}

.task-description {
  margin: 8px 0;
  font-size: 13px;
  color: #64748b;
  line-height: 1.4;
}

.task-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 12px;
}

.task-assignee {
  display: flex;
  align-items: center;
  gap: 8px;
}

.assignee-avatar {
  width: 24px;
  height: 24px;
  background: #3b82f6;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 600;
}

.assignee-name {
  font-size: 12px;
  color: #64748b;
}

.task-due-date {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #64748b;
}

.add-task-btn {
  width: 100%;
  background: none;
  border: 2px dashed #cbd5e1;
  color: #64748b;
  padding: 16px;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  font-size: 14px;
  transition: all 0.2s;
}

.add-task-btn:hover {
  border-color: #3b82f6;
  color: #3b82f6;
  background: #eff6ff;
}

/* Modals */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  padding: 24px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-content h3 {
  margin: 0 0 20px 0;
  color: #1e293b;
  font-size: 18px;
  font-weight: 600;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #374151;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

.btn-primary,
.btn-secondary {
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-secondary {
  background: #f1f5f9;
  color: #64748b;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

/* Responsive */
@media (max-width: 768px) {
  .kanban-columns {
    flex-direction: column;
  }
  
  .kanban-column {
    min-width: auto;
  }
}
</style>
