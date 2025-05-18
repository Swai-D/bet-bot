<template>
  <div class="dashboard">
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Quick Actions</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <button 
                  class="btn btn-primary w-100" 
                  @click="runScraper"
                  :disabled="isLoading"
                >
                  <span v-if="isLoading" class="spinner-border spinner-border-sm me-2"></span>
                  {{ isLoading ? 'Running...' : 'Run Scraper' }}
                </button>
              </div>
              <div class="col-md-4">
                <button 
                  class="btn btn-success w-100" 
                  @click="saveSelectedPredictions"
                  :disabled="!hasSelectedPredictions"
                >
                  Save Selected Predictions
                </button>
              </div>
              <div class="col-md-4">
                <button 
                  class="btn btn-info w-100" 
                  @click="exportPredictions"
                  :disabled="!predictions.length"
                >
                  Export Predictions
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Predictions</h5>
          </div>
          <div class="card-body">
            <predictions-list 
              :predictions="predictions"
              @toggle-selection="togglePredictionSelection"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import PredictionsList from '../components/PredictionsList.vue'
import axios from 'axios'

export default {
  name: 'Dashboard',
  components: {
    PredictionsList
  },
  data() {
    return {
      predictions: [],
      isLoading: false
    }
  },
  computed: {
    hasSelectedPredictions() {
      return this.predictions.some(p => p.selected)
    }
  },
  methods: {
    async runScraper() {
      try {
        this.isLoading = true
        const response = await axios.post('/api/predictions/run-scraper')
        
        if (response.data.success) {
          this.predictions = response.data.predictions
        } else {
          console.error('Failed to run scraper:', response.data.message)
        }
      } catch (error) {
        console.error('Error running scraper:', error)
      } finally {
        this.isLoading = false
      }
    },
    togglePredictionSelection(prediction) {
      const index = this.predictions.findIndex(p => p.id === prediction.id)
      if (index !== -1) {
        this.predictions[index].selected = !this.predictions[index].selected
      }
    },
    async saveSelectedPredictions() {
      try {
        const selectedPredictions = this.predictions.filter(p => p.selected)
        const response = await axios.post('/api/predictions/save-selected', {
          predictions: selectedPredictions
        })
        
        if (response.data.success) {
          // Update predictions after saving
          this.predictions = this.predictions.map(p => ({
            ...p,
            selected: false
          }))
        } else {
          console.error('Failed to save predictions:', response.data.message)
        }
      } catch (error) {
        console.error('Error saving predictions:', error)
      }
    },
    exportPredictions() {
      const csv = this.convertToCSV(this.predictions)
      const blob = new Blob([csv], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `predictions-${new Date().toISOString().split('T')[0]}.csv`
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
      window.URL.revokeObjectURL(url)
    },
    convertToCSV(predictions) {
      const headers = ['Match', 'Date', 'Tips', 'Odds']
      const rows = predictions.map(p => [
        p.teams,
        p.date,
        p.tips,
        p.odds
      ])
      return [headers, ...rows].map(row => row.join(',')).join('\n')
    }
  },
  async created() {
    try {
      const response = await axios.get('/api/predictions')
      if (response.data.success) {
        this.predictions = response.data.predictions
      }
    } catch (error) {
      console.error('Error fetching predictions:', error)
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 20px;
}

.card {
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn {
  margin-bottom: 10px;
}

@media (min-width: 768px) {
  .btn {
    margin-bottom: 0;
  }
}
</style> 