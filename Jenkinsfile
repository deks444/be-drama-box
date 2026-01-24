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

        stage('Deploy') {
            steps {
                script {
                    sh "docker stop ${IMAGE_NAME} || true && docker rm ${IMAGE_NAME} || true"
                    
                    // Kita asumsikan dramabox-auth-env berisi konten lengkap .env atau key tertentu
                    sh """
                        docker run -d \
                        --name ${IMAGE_NAME} \
                        -p 9004:9004 \
                        -e APP_KEY=${DRAMABOX_AUTH} \
                        ${IMAGE_NAME}:${BUILD_NUMBER}
                    """
                }
            }
        }
    }
}
