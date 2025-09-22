<template>
  <div class="boards-container">
    <header class="boards-header">
      <div class="header-content">
        <h1>Tableaux</h1>
        <button class="btn btn-create-board" @click="showCreateBoardModal = true">
          <span class="btn-icon">+</span>
          Créer un tableau
        </button>
      </div>
    </header>

    <div class="boards-content">
      <div class="boards-grid">
        <div v-for="board in boards" :key="board.id" class="board-card" @click="openBoard(board)">
          <div class="board-card-header">
            <div class="board-icon" :style="{ backgroundColor: board.color }">
              {{ board.name.charAt(0).toUpperCase() }}
            </div>
            <div class="board-menu" @click.stop>
              <button class="board-menu-btn" @click="toggleBoardMenu(board.id)">⋯</button>
              <div v-if="activeBoardMenu === board.id" class="board-menu-dropdown">
                <button @click="editBoard(board)">Modifier</button>
                <button @click="duplicateBoard(board)">Dupliquer</button>
                <button @click="deleteBoard(board.id)" class="delete-btn">Supprimer</button>
              </div>
            </div>
          </div>
          <div class="board-card-content">
            <h3>{{ board.name }}</h3>
            <p>{{ board.description || 'Aucune description' }}</p>
            <div class="board-meta">
              <span class="board-lists">{{ board.listsCount }} listes</span>
              <span class="board-cards">{{ board.cardsCount }} cartes</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de création de tableau -->
    <div v-if="showCreateBoardModal" class="modal-overlay" @click="closeCreateBoardModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h2>Créer un nouveau tableau</h2>
          <button class="close-btn" @click="closeCreateBoardModal">✕</button>
        </div>
        <form @submit.prevent="createBoard" class="modal-form">
          <div class="form-group">
            <label for="boardName">Nom du tableau</label>
            <input
              id="boardName"
              v-model="newBoard.name"
              type="text"
              placeholder="Ex: Projet Marketing Q4"
              required
            />
          </div>
          <div class="form-group">
            <label for="boardDescription">Description (optionnel)</label>
            <textarea
              id="boardDescription"
              v-model="newBoard.description"
              placeholder="Décrivez le but de ce tableau..."
              rows="3"
            ></textarea>
          </div>
          <div class="form-group">
            <label for="boardColor">Couleur</label>
            <div class="color-picker">
              <div
                v-for="color in boardColors"
                :key="color"
                class="color-option"
                :class="{ active: newBoard.color === color }"
                :style="{ backgroundColor: color }"
                @click="newBoard.color = color"
              ></div>
            </div>
          </div>
          <div class="form-group">
            <label for="boardVisibility">Visibilité</label>
            <select id="boardVisibility" v-model="newBoard.visibility">
              <option value="private">Privé</option>
              <option value="workspace">Espace de travail</option>
              <option value="public">Public</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeCreateBoardModal">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary">Créer le tableau</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal d'édition de tableau -->
    <div v-if="showEditBoardModal" class="modal-overlay" @click="closeEditBoardModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h2>Modifier le tableau</h2>
          <button class="close-btn" @click="closeEditBoardModal">✕</button>
        </div>
        <form @submit.prevent="updateBoard" class="modal-form">
          <div class="form-group">
            <label for="editBoardName">Nom du tableau</label>
            <input id="editBoardName" v-model="editingBoard.name" type="text" required />
          </div>
          <div class="form-group">
            <label for="editBoardDescription">Description</label>
            <textarea
              id="editBoardDescription"
              v-model="editingBoard.description"
              rows="3"
            ></textarea>
          </div>
          <div class="form-group">
            <label for="editBoardColor">Couleur</label>
            <div class="color-picker">
              <div
                v-for="color in boardColors"
                :key="color"
                class="color-option"
                :class="{ active: editingBoard.color === color }"
                :style="{ backgroundColor: color }"
                @click="editingBoard.color = color"
              ></div>
            </div>
          </div>
          <div class="form-group">
            <label for="editBoardVisibility">Visibilité</label>
            <select id="editBoardVisibility" v-model="editingBoard.visibility">
              <option value="private">Privé</option>
              <option value="workspace">Espace de travail</option>
              <option value="public">Public</option>
            </select>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-secondary" @click="closeEditBoardModal">
              Annuler
            </button>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { authService } from '@/services/authService'

const router = useRouter()
const boards = ref([])
const showCreateBoardModal = ref(false)
const showEditBoardModal = ref(false)
const editingBoard = ref({})
const activeBoardMenu = ref(null)
const loading = ref(false)

const newBoard = ref({
  name: '',
  description: '',
  color: '#0079bf',
  visibility: 'private',
})

const boardColors = [
  '#0079bf',
  '#d29034',
  '#519839',
  '#b04632',
  '#89609e',
  '#cd5a91',
  '#4bbf6b',
  '#29cce5',
]

onMounted(async () => {
  if (!authService.isAuthenticated()) {
    router.push('/login')
    return
  }
  await loadBoards()
})

const loadBoards = async () => {
  try {
    loading.value = true
    const response = await fetch('http://localhost:8000/api/boards', {
      headers: {
        Authorization: `Bearer ${authService.getToken()}`,
        'Content-Type': 'application/json',
      },
    })

    if (response.ok) {
      const data = await response.json()
      boards.value = data.map((board) => ({
        ...board,
        listsCount: board.lists?.length || 0,
        cardsCount: board.lists?.reduce((total, list) => total + (list.cards?.length || 0), 0) || 0,
      }))
    }
  } catch (error) {
    console.error('Erreur lors du chargement des tableaux:', error)
  } finally {
    loading.value = false
  }
}

const createBoard = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/boards', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${authService.getToken()}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(newBoard.value),
    })

    if (response.ok) {
      await loadBoards()
      closeCreateBoardModal()
      resetNewBoard()
    }
  } catch (error) {
    console.error('Erreur lors de la création du tableau:', error)
  }
}

const updateBoard = async () => {
  try {
    const response = await fetch(`http://localhost:8000/api/boards/${editingBoard.value.id}`, {
      method: 'PUT',
      headers: {
        Authorization: `Bearer ${authService.getToken()}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(editingBoard.value),
    })

    if (response.ok) {
      await loadBoards()
      closeEditBoardModal()
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour du tableau:', error)
  }
}

const deleteBoard = async (boardId) => {
  if (!confirm('Êtes-vous sûr de vouloir supprimer ce tableau ?')) return

  try {
    const response = await fetch(`http://localhost:8000/api/boards/${boardId}`, {
      method: 'DELETE',
      headers: {
        Authorization: `Bearer ${authService.getToken()}`,
      },
    })

    if (response.ok) {
      await loadBoards()
    }
  } catch (error) {
    console.error('Erreur lors de la suppression du tableau:', error)
  }
}

const duplicateBoard = (board) => {
  newBoard.value = {
    name: `${board.name} (copie)`,
    description: board.description,
    color: board.color,
    visibility: board.visibility,
  }
  showCreateBoardModal.value = true
}

const editBoard = (board) => {
  editingBoard.value = { ...board }
  showEditBoardModal.value = true
}

const openBoard = (board) => {
  router.push(`/board/${board.id}`)
}

const toggleBoardMenu = (boardId) => {
  activeBoardMenu.value = activeBoardMenu.value === boardId ? null : boardId
}

const closeCreateBoardModal = () => {
  showCreateBoardModal.value = false
  resetNewBoard()
}

const closeEditBoardModal = () => {
  showEditBoardModal.value = false
  editingBoard.value = {}
}

const resetNewBoard = () => {
  newBoard.value = {
    name: '',
    description: '',
    color: '#0079bf',
    visibility: 'private',
  }
}
</script>

<style scoped>
.boards-container {
  min-height: 100vh;
  background: #ffffff;
  color: #2c3e50;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.boards-header {
  background: #ffffff;
  padding: 2rem;
  border-bottom: 1px solid #e1e5e9;
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  max-width: 1200px;
  margin: 0 auto;
}

.header-content h1 {
  font-size: 2rem;
  font-weight: bold;
  color: #2c3e50;
  margin: 0;
}

.btn-create-board {
  background: #0079bf;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.btn-create-board:hover {
  background: #005a8b;
  transform: translateY(-2px);
}

.btn-icon {
  font-size: 1.2rem;
  font-weight: bold;
}

.boards-content {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.boards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.board-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border: 1px solid #e1e5e9;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.board-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.board-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.board-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1.5rem;
}

.board-menu {
  position: relative;
}

.board-menu-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 4px;
  color: #5a6c7d;
  transition: all 0.2s ease;
}

.board-menu-btn:hover {
  background: #f8f9fa;
  color: #2c3e50;
}

.board-menu-dropdown {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  border: 1px solid #e1e5e9;
  min-width: 150px;
  z-index: 1000;
}

.board-menu-dropdown button {
  width: 100%;
  padding: 0.75rem 1rem;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s ease;
  color: #2c3e50;
}

.board-menu-dropdown button:hover {
  background: #f8f9fa;
}

.board-menu-dropdown .delete-btn {
  color: #dc3545;
}

.board-menu-dropdown .delete-btn:hover {
  background: #fee;
}

.board-card-content h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
}

.board-card-content p {
  margin: 0 0 1rem 0;
  color: #5a6c7d;
  font-size: 0.9rem;
  line-height: 1.4;
}

.board-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.8rem;
  color: #6b7280;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.modal {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid #e1e5e9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  color: #2c3e50;
  font-size: 1.5rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #5a6c7d;
  padding: 0.25rem;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #f8f9fa;
  color: #2c3e50;
}

.modal-form {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #2c3e50;
}

.form-group input,
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e1e5e9;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
  outline: none;
  border-color: #0079bf;
}

.color-picker {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.color-option {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  cursor: pointer;
  border: 3px solid transparent;
  transition: all 0.2s ease;
}

.color-option:hover {
  transform: scale(1.1);
}

.color-option.active {
  border-color: #2c3e50;
  transform: scale(1.1);
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  margin-top: 2rem;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover {
  background: #4b5563;
}

.btn-primary {
  background: #0079bf;
  color: white;
}

.btn-primary:hover {
  background: #005a8b;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .boards-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }
}
</style>
