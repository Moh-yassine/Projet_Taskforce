import { describe, it, expect, beforeEach, vi } from 'vitest'
import { projectService } from '../projectService'

// Mock fetch
global.fetch = vi.fn()

describe('ProjectService', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getProjects', () => {
    it('returns projects when API call is successful', async () => {
      const mockProjects = [
        {
          id: 1,
          name: 'Test Project 1',
          description: 'Description 1',
          status: 'in_progress'
        },
        {
          id: 2,
          name: 'Test Project 2',
          description: 'Description 2',
          status: 'completed'
        }
      ]

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: true,
        json: async () => mockProjects
      })

      const projects = await projectService.getProjects()
      
      expect(projects).toEqual(mockProjects)
      expect(global.fetch).toHaveBeenCalledWith(
        'http://127.0.0.1:8000/api/projects',
        expect.objectContaining({
          method: 'GET',
          headers: expect.objectContaining({
            'Content-Type': 'application/json'
          })
        })
      )
    })

    it('throws error when API call fails', async () => {
      ;(global.fetch as any).mockResolvedValueOnce({
        ok: false,
        status: 500
      })

      await expect(projectService.getProjects()).rejects.toThrow('Erreur lors de la récupération des projets')
    })

    it('redirects to login when unauthorized', async () => {
      const mockLocation = { href: '' }
      Object.defineProperty(window, 'location', {
        value: mockLocation,
        writable: true
      })

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: false,
        status: 401
      })

      await expect(projectService.getProjects()).rejects.toThrow('Non authentifié')
    })
  })

  describe('createProject', () => {
    it('creates project successfully', async () => {
      const projectData = {
        name: 'New Project',
        description: 'New Description',
        status: 'planning'
      }

      const mockResponse = {
        id: 1,
        ...projectData
      }

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse
      })

      const result = await projectService.createProject(projectData)
      
      expect(result).toEqual(mockResponse)
      expect(global.fetch).toHaveBeenCalledWith(
        'http://127.0.0.1:8000/api/projects',
        expect.objectContaining({
          method: 'POST',
          headers: expect.objectContaining({
            'Content-Type': 'application/json'
          }),
          body: JSON.stringify(projectData)
        })
      )
    })

    it('throws error when creation fails', async () => {
      const projectData = {
        name: 'New Project',
        description: 'New Description',
        status: 'planning'
      }

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: false,
        status: 400,
        json: async () => ({ message: 'Validation failed' })
      })

      await expect(projectService.createProject(projectData)).rejects.toThrow('Validation failed')
    })
  })

  describe('updateProject', () => {
    it('updates project successfully', async () => {
      const projectId = 1
      const updateData = {
        name: 'Updated Project',
        status: 'in_progress'
      }

      const mockResponse = {
        id: projectId,
        ...updateData
      }

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: true,
        json: async () => mockResponse
      })

      const result = await projectService.updateProject(projectId, updateData)
      
      expect(result).toEqual(mockResponse)
      expect(global.fetch).toHaveBeenCalledWith(
        `http://127.0.0.1:8000/api/projects/${projectId}`,
        expect.objectContaining({
          method: 'PUT',
          headers: expect.objectContaining({
            'Content-Type': 'application/json'
          }),
          body: JSON.stringify(updateData)
        })
      )
    })
  })

  describe('deleteProject', () => {
    it('deletes project successfully', async () => {
      const projectId = 1

      ;(global.fetch as any).mockResolvedValueOnce({
        ok: true,
        json: async () => ({ message: 'Project deleted' })
      })

      const result = await projectService.deleteProject(projectId)
      
      expect(result.message).toBe('Project deleted')
      expect(global.fetch).toHaveBeenCalledWith(
        `http://127.0.0.1:8000/api/projects/${projectId}`,
        expect.objectContaining({
          method: 'DELETE'
        })
      )
    })
  })
})
