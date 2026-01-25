pipeline {
    agent any

    environment {
        // Mengambil kredensial Jenkins (Secret File) dan menyimpannya di path .env
        ENV_FILE = credentials('dramabox-auth-env')
    }

    stages {
        stage('Checkout SCM') {
            steps {
                // Checkout dilakukan otomatis oleh Jenkins jika menggunakan mode SCM
                checkout scm
            }
        }

        stage('Setup Environment') {
            steps {
                script {
                    // Menyalin file kredensial ke root folder project
                    sh "cp ${ENV_FILE} .env"
                }
            }
        }

        stage('Build & Test') {
            steps {
                echo 'Building...'
                // Contoh: sh 'npm install && npm run build'
            }
        }

        stage('Deploy') {
            steps {
                echo 'Deploying application...'
                // Contoh: sh 'docker-compose up -d --build'
            }
        }
    }

    post {
        success {
            echo "Successfully deployed drama-box-auth!"
        }
        failure {
            echo "Build failed. Please check the console output."
        }
    }
}
