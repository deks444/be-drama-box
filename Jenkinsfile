pipeline {
    agent any
    
    triggers {
        githubPush()
    }

    tools {
        // Pastikan nama 'docker-latest' SAMA PERSIS dengan di Global Tool Configuration
        dockerTool 'docker-latest'
    }

    environment {
        DRAMABOX_AUTH = credentials('dramabox-auth-env')
        APP_PORT = '9004'
        IMAGE_NAME = 'dramabox-app'
    }

    stages {
        stage('Docker Version Check') {
            steps {
                // Menggunakan script untuk verifikasi path
                script {
                    try {
                        sh 'docker --version'
                    } catch (Exception e) {
                        echo "Docker tidak ditemukan di PATH, mencoba mencari secara manual..."
                        // Fallback: Jika 'which docker' gagal, kita cetak PATH untuk debug
                        sh 'printenv PATH'
                        error "Docker executable tetap tidak ditemukan. Cek Global Tool Configuration."
                    }
                }
            }
        }

        stage('Build & Deploy') {
            steps {
                script {
                    // Build Image
                    sh "docker build -t ${IMAGE_NAME}:${BUILD_NUMBER} ."
                    
                    // Deployment
                    sh "docker stop ${IMAGE_NAME} || true"
                    sh "docker rm ${IMAGE_NAME} || true"
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
}
