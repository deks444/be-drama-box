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
        stage('Setup Docker Tool') {
            steps {
                script {
                    def dockerToolPath = tool name: 'docker-latest', type: 'dockerTool'
                    env.PATH = "${dockerToolPath}/bin:${env.PATH}"
                }
            }
        }

        stage('Check Workspace') {
            steps {
                // Perintah ini untuk melihat daftar file yang ada di workspace saat ini
                sh "ls -la" 
            }
        }

        stage('Build & Run') {
            steps {
                script {
                    // Cek lokasi saat ini dan daftar file yang ada
                    sh "pwd"
                    sh "ls -R" // Ini akan melist SEMUA file hingga ke sub-folder

                    // Ganti '.' dengan folder yang berisi Dockerfile jika tidak ada di root
                    // Contoh: sh "docker build -t ${IMAGE_NAME}:${BUILD_NUMBER} ./sub-folder-anda"
                    sh "docker build -t ${IMAGE_NAME}:${BUILD_NUMBER} ."

                    sh "docker stop ${IMAGE_NAME} || true && docker rm ${IMAGE_NAME} || true"

                    sh """
                        docker run -d \
                        --name ${IMAGE_NAME} \
                        -p ${APP_PORT}:${APP_PORT} \
                        -e AUTH_KEY='${DRAMABOX_AUTH}' \
                        ${IMAGE_NAME}:${BUILD_NUMBER}
                    """
                }
            }
        }
    }
}
