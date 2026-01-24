pipeline {
    agent any
    
    triggers {
        githubPush()
    }

    environment {
        DRAMABOX_AUTH = credentials('dramabox-auth-env')
        APP_PORT = '9004'
        IMAGE_NAME = 'dramabox-app'
    }

    stages {
        stage('Initialize Docker Path') {
            steps {
                script {
                    // Mengambil path absolut dari tool 'docker-latest'
                    def dockerHome = tool name: 'docker-latest', type: 'dockerTool'
                    // Menambahkan folder bin docker ke PATH agar perintah 'docker' bisa dipanggil
                    env.PATH = "${dockerHome}/bin:${env.PATH}"
                }
            }
        }

        stage('Build & Deploy') {
            steps {
                script {
                    echo "Menggunakan Docker dari: ${sh(script: 'which docker', returnStdout: true).trim()}"
                    
                    // Build Image
                    sh "docker build -t ${IMAGE_NAME}:${BUILD_NUMBER} ."
                    
                    // Cleanup & Run
                    sh "docker stop ${IMAGE_NAME} || true && docker rm ${IMAGE_NAME} || true"
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
