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
    }

    stages {
        stage('Checkout SCM') {
            steps {
                // Jenkins akan otomatis mengambil kode dari repo yang dikonfigurasi di Job
                checkout scm
            }
        }
    }

        stage('Prepare Environment') {
            steps {
                echo 'Injecting .env file...'
                script {
                    // Masukkan ID kredensial langsung di sini untuk menghindari error "MissingProperty"
                    // Ganti 'dramabox-auth-env' jika ID di Jenkins Anda berbeda
                    withCredentials([file(credentialsId: 'dramabox-auth-env', variable: 'SECRET_PATH')]) {
                        sh 'cp "$SECRET_PATH" .env'
                    }
                }
            }
        }

        stage('Cleanup & Build') {
            steps {
                script {
                    echo 'Stopping old containers and building new ones...'
                    // Gunakan docker compose (V2)
                    sh 'docker compose down --remove-orphans || true'
                    sh 'docker compose up --build -d'
                }
            }
        }

    post {
        always {
            // Hapus .env dari workspace demi keamanan
            sh 'rm -f .env'
            cleanWs()
        }
        success {
            echo "Aplikasi berjalan di background pada port 9004"
        }
    }
