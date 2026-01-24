pipeline {
    agent any
    
    triggers {
        githubPush()
    }

    tools {
        // Menggunakan tool docker yang sudah dikonfigurasi di Jenkins Global Tool Configuration
        dockerTool 'docker-latest'
    }

    environment {
        // Mengambil kredensial dari Jenkins Credentials Store
        DRAMABOX_AUTH = credentials('dramabox-auth-env')
        APP_PORT = '9004'
        IMAGE_NAME = 'dramabox-app'
    }

    stages {
        stage('Checkout') {
            steps {
                checkout scm
            }
        }

        stage('Build Image') {
            steps {
                script {
                    // Membangun image menggunakan Docker tool
                    sh "docker build -t ${IMAGE_NAME}:${BUILD_NUMBER} ."
                }
            }
        }

        stage('Deploy Container') {
            steps {
                script {
                    // Stop container lama jika ada (agar port 9004 tidak bentrok)
                    sh "docker stop ${IMAGE_NAME} || true && docker rm ${IMAGE_NAME} || true"
                    
                    // Menjalankan container baru
                    // Menggunakan kredensial dari environment variable
                    sh """
                        docker run -d \
                        --name ${IMAGE_NAME} \
                        -p ${APP_PORT}:${APP_PORT} \
                        -e AUTH_KEY=${DRAMABOX_AUTH} \
                        ${IMAGE_NAME}:${BUILD_NUMBER}
                    """
                }
            }
        }
    }

    post {
        success {
            echo "Pipeline berhasil! Aplikasi berjalan di port ${APP_PORT}"
        }
        failure {
            echo "Pipeline gagal. Silakan cek log build."
        }
    }
}
    
