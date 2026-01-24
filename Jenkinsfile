pipeline {
    agent any
    
    triggers {
        githubPush()
    }

    environment {
        // Load credentials - Masking sudah oke di log sebelumnya
        DRAMABOX_AUTH = credentials('dramabox-auth-env')
        APP_PORT = '9004'
        IMAGE_NAME = 'dramabox-app'
    }

    stages {
        stage('Setup Docker Tool') {
            steps {
                script {
                    // 1. Ambil path dari tool 'docker-latest'
                    def dockerToolPath = tool name: 'docker-latest', type: 'dockerTool'
                    
                    // 2. Tambahkan folder bin dari tool tersebut ke PATH secara manual
                    def dockerBin = "${dockerToolPath}/bin"
                    env.PATH = "${dockerBin}:${env.PATH}"
                    
                    echo "Docker Tool Path: ${dockerBin}"
                    sh "docker --version" // Sekarang ini seharusnya berhasil
                }
            }
        }

        stage('Build & Run') {
            steps {
                script {
                    // Build image
                    sh "docker build -t ${IMAGE_NAME}:${BUILD_NUMBER} ."

                    // Stop & Remove container lama jika ada
                    sh "docker stop ${IMAGE_NAME} || true && docker rm ${IMAGE_NAME} || true"

                    // Jalankan container dengan Port 9004 dan Env dari credentials
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
