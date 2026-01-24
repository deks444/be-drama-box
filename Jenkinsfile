pipeline {
    agent any

    triggers {
        githubPush()
    }

    environment {
        APP_NAME = 'drama-box-auth'
        DOT_ENV_FILE = credentials('dramabox-auth-env')
    }

    stages {
        stage('Checkout') {
            steps {
                git branch: 'main', url: 'https://github.com/PTMajuJayaMakmur/drama-box-auth.git'
            }
        }

        stage('Prepare Environment') {
            steps {
                sh "cp ${DOT_ENV_FILE} .env"
            }
        }

        stage('Cleanup & Stop Old Process') {
            steps {
                echo 'Cleaning up port 9004 and old containers...'
                script {
                    // Berhenti dan hapus kontainer lama
                    sh 'docker-compose down --remove-orphans'
                    // Hapus image sampah agar disk tidak penuh
                    sh 'docker image prune -f'
                }
            }
        }

        stage('Build & Run Background') {
            steps {
                echo 'Starting application on port 9004...'
                sh 'docker-compose up --build -d'
            }
        }

        stage('Verify Access') {
            steps {
                echo 'Verifying application is live on port 9004...'
                // Menunggu 5 detik agar aplikasi startup sempurna
                sleep 5
                sh 'curl -I http://localhost:9004 || echo "Warning: Port 9004 not responding yet"'
            }
        }
    }

    post {
        always {
            sh 'rm -f .env'
            cleanWs()
        }
    }
}
