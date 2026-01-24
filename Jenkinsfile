pipeline {
    agent any
    
    triggers {
        githubPush()
    }
    
    // Menggunakan tool Docker yang sudah diinstal di Jenkins
    tools {
        dockerTool 'docker-latest' 
    }

    environment {
        APP_NAME = 'drama-box-auth'
        // Kredensial .env dari Jenkins Credentials Manager
        DOT_ENV_FILE = credentials('dramabox-auth-env')
    }

    stages {
        stage('Checkout SCM') {
            steps {
                // Jenkins akan otomatis mengambil kode dari repo yang dikonfigurasi di Job
                checkout scm
            }
        }

        stage('Prepare Environment') {
            steps {
                echo 'Injecting .env file...'
                sh "cp ${DOT_ENV_FILE} .env"
            }
        }

        stage('Cleanup & Down') {
            steps {
                echo 'Stopping existing containers...'
                // '|| true' agar tidak error jika kontainer belum ada
                sh 'docker compose down --remove-orphans || true'
            }
        }

        stage('Build & Run Background') {
            steps {
                echo 'Starting application on port 9004...'
                // Menjalankan di background (detached mode)
                sh 'docker compose up --build -d'
            }
        }

        stage('Verify Process') {
            steps {
                sh 'docker ps | grep ${APP_NAME}'
                echo "Deployment Complete. Access at http://your-ip:9004"
            }
        }
    }

    post {
        always {
            // Menghapus file .env sensitif agar tidak tertinggal di workspace
            sh 'rm -f .env'
        }
    }
}
