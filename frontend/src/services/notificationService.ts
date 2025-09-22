import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api'

export interface Notification {
  id: number
  title: string
  message: string
  type: 'info' | 'warning' | 'error' | 'success'
  isRead: boolean
  createdAt: string
  userId?: number
}

class NotificationService {
  /**
   * Récupère toutes les notifications de l'utilisateur connecté
   */
  async getNotifications(): Promise<Notification[]> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.get(`${API_BASE_URL}/notifications/my-notifications`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la récupération des notifications:', error)
      throw error
    }
  }

  /**
   * Marque une notification comme lue
   */
  async markAsRead(notificationId: number): Promise<Notification> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.put(
        `${API_BASE_URL}/notifications/${notificationId}/read`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      return response.data
    } catch (error) {
      console.error('Erreur lors du marquage de la notification comme lue:', error)
      throw error
    }
  }

  /**
   * Marque une notification comme non lue
   */
  async markAsUnread(notificationId: number): Promise<Notification> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.put(
        `${API_BASE_URL}/notifications/${notificationId}/unread`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      return response.data
    } catch (error) {
      console.error('Erreur lors du marquage de la notification comme non lue:', error)
      throw error
    }
  }

  /**
   * Bascule l'état de lecture d'une notification
   */
  async toggleReadStatus(notificationId: number): Promise<Notification> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.put(
        `${API_BASE_URL}/notifications/${notificationId}/toggle`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      return response.data
    } catch (error) {
      console.error("Erreur lors du basculement de l'état de la notification:", error)
      throw error
    }
  }

  /**
   * Marque toutes les notifications comme lues
   */
  async markAllAsRead(): Promise<void> {
    try {
      const token = localStorage.getItem('authToken')
      await axios.patch(
        `${API_BASE_URL}/notifications/read-all`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
    } catch (error) {
      console.error('Erreur lors du marquage de toutes les notifications comme lues:', error)
      throw error
    }
  }

  /**
   * Crée une nouvelle notification
   */
  async createNotification(notification: Partial<Notification>): Promise<Notification> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.post(`${API_BASE_URL}/notifications`, notification, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
      return response.data
    } catch (error) {
      console.error('Erreur lors de la création de la notification:', error)
      throw error
    }
  }

  /**
   * Supprime une notification
   */
  async deleteNotification(notificationId: number): Promise<void> {
    try {
      const token = localStorage.getItem('authToken')
      await axios.delete(`${API_BASE_URL}/notifications/${notificationId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      })
    } catch (error) {
      console.error('Erreur lors de la suppression de la notification:', error)
      throw error
    }
  }

  /**
   * Vérifie les alertes de surcharge de travail
   */
  async checkWorkloadAlerts(): Promise<any> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.post(
        `${API_BASE_URL}/alerts/check-workload`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      return response.data
    } catch (error) {
      console.error('Erreur lors de la vérification des alertes de surcharge:', error)
      throw error
    }
  }

  /**
   * Vérifie les alertes de retard de tâches
   */
  async checkDelayAlerts(): Promise<any> {
    try {
      const token = localStorage.getItem('authToken')
      const response = await axios.post(
        `${API_BASE_URL}/alerts/check-delays`,
        {},
        {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        },
      )
      return response.data
    } catch (error) {
      console.error('Erreur lors de la vérification des alertes de retard:', error)
      throw error
    }
  }
}

export const notificationService = new NotificationService()
