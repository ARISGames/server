/*
 * ShapeMatcher.cpp
 *
 *  Created on: Jul 16, 2011
 *      Author: bobbyzhu
 */

#include "opencv2/highgui/highgui.hpp"
#include "opencv2/calib3d/calib3d.hpp"
#include "opencv2/imgproc/imgproc.hpp"
#include "opencv2/features2d/features2d.hpp"

#include <iostream>
#include <fstream>
#include <sys/types.h>
#include <dirent.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <cstdio>
#include <cstdlib>
#include <stdint.h>

#include "MatchParam.h"

using namespace std;
using namespace cv;

#define SIFT_LENGTH 128
#define DEBUG

#ifdef DEBUG
#       define Log(fmt, ...) printf(("/* [%s:%d]: " fmt " */ \n"), __FILE__, __LINE__, ##__VA_ARGS__);
#else
#       define Log(...)
#endif


struct ShapeDescriptor
{
	Point2f coord;
	float feature[SIFT_LENGTH];
	float orientation;
	int object;
};

struct GlobalParam
{
	int Na;
	int Nr;
	double epsilon_p;
	double alpha;
	double beta;

	// iteration
	int max_iter;
	double iter_threshold;

	// match
	double min_match_ratio;
};

inline double siftdistance(const float a[SIFT_LENGTH], const float b[SIFT_LENGTH])
{
	double dist=0;
	for (int i=0; i<SIFT_LENGTH; i++) {
		double diff=static_cast<double>(a[i]-b[i]);
		dist+=diff*diff;
	}
	return sqrt(dist);
}

inline double anglediff(float a, float b)
{
	if (a<0)
		a+=2*CV_PI;
	if (b<0)
		b+=2*CV_PI;
	double diff=fabs(static_cast<double>(a-b));
	if (diff>CV_PI)
		diff=2*CV_PI-diff;
	return diff;
}

void AddShapeDescriptor(const Mat& image, int objectid, vector<ShapeDescriptor>& descriptors)
{
	SiftFeatureDetector* detector=new SiftFeatureDetector();
	SiftDescriptorExtractor* extractor=new SiftDescriptorExtractor();

	Log("Extracting keypoints\n");
	vector<KeyPoint> keypoints;
	detector->detect(image, keypoints);
	Log("Extracted %ld keypoints\n", keypoints.size());
	Mat keydesc;
	extractor->compute(image, keypoints, keydesc);
	Log("Feature length=%d\n", extractor->descriptorSize());
	Log("Feature type=%d\n", extractor->descriptorType());
	Log("keydesc dim=%dx%d\n", keydesc.rows, keydesc.cols);
	CV_Assert(extractor->descriptorSize()==SIFT_LENGTH);
	CV_Assert(extractor->descriptorType()==CV_32F);

	/*
	// Debug: drawing these keypoints
	Mat gray_img, key_img;
	namedWindow("debugWindow");
	cvtColor(image, gray_img, CV_RGB2GRAY);
	drawKeypoints(gray_img, keypoints, key_img);
	imshow("debugWindow", key_img);
	waitKey();
	// end debug
	*/

	for (int i=0; i<keypoints.size(); i++) {
		ShapeDescriptor sd;
		sd.coord=keypoints[i].pt;
		sd.orientation=keypoints[i].angle;
//		Log("Angle=%f\n", sd.orientation);
		sd.object=objectid;
		memcpy(sd.feature, keydesc.ptr<float>(i), sizeof(float)*SIFT_LENGTH);
		descriptors.push_back(sd);
	}

	delete detector;
	delete extractor;
}

void ConstructMatrixDistance(
		const vector<ShapeDescriptor>& logo,
		Mat& D)
{
	// D is a mapping from logo to test (logoxtest)
	D.create(logo.size(), logo.size(), CV_64F);

	for (int r=0; r<D.rows; r++) {
		double* data=D.ptr<double>(r);
		for (int c=0; c<r; c++) {
			*data=D.at<double>(c, r);
			data++;
		}
		for (int c=r; c<D.cols; c++) {
			*data=siftdistance(logo[r].feature, logo[c].feature);
			data++;
		}
	}
}

void ConstructMatrixAdjacency(
		const vector<ShapeDescriptor>& logo,
		const Mat& D,		// for speed up purpose
		const GlobalParam& param,
		vector<Mat>& P)
{
	P.resize(param.Na*param.Nr);
	for (int i=0; i<param.Na*param.Nr; i++) {
		P[i].create(logo.size(), logo.size(), CV_64F);
		P[i]=0.0;
	}

	for (int r=0; r<logo.size(); r++) {
		for (int c=0; c<logo.size(); c++) {
			if (logo[r].object==logo[c].object) {
				// same object
				double dist=D.at<double>(r,c);
				if (dist<=param.epsilon_p) {
					int rho_bin=static_cast<int>(ceil(dist*param.Nr/param.epsilon_p));
					if (rho_bin==0)
						rho_bin=1;	// a simple hack to solve the dist=0 problem
					CV_Assert(rho_bin>=1 && rho_bin<=param.Nr);

					double a2=atan2(logo[c].coord.y-logo[r].coord.y, logo[c].coord.x-logo[r].coord.x);
					double angle=anglediff(logo[r].orientation/180.0*CV_PI, a2);
					int theta_bin=static_cast<int>(ceil(angle*param.Na/CV_PI));
					if (theta_bin==0)
						theta_bin=1;	// a simple hack to solve the theta_bin=0 problem
					CV_Assert(theta_bin>=1 && theta_bin<=param.Na);

					int matrix_index=(rho_bin-1)*param.Na+(theta_bin-1);
//					Log("Matrix index=%d\n", matrix_index);
					P[matrix_index].at<double>(r, c)=1;
				} else {
					// Log("Dist out of range.\n");
				}
			}
		}
	}
}



void CheckForSimilarity(
		const Mat& D,
		const vector<Mat>& P,
		const GlobalParam& param,
		Mat& K)
{
	CV_Assert(D.rows==D.cols);
	int nLogo=D.rows;

	Mat u=Mat::ones(nLogo, nLogo, CV_64F);
	Mat c=Mat::zeros(nLogo, nLogo, CV_64F);
	for (int i=0; i<P.size(); i++) {
		c+=P[i]*u*P[i].t()+P[i].t()*u*P[i];
	}
	double c2=param.alpha/param.beta*norm(c, NORM_INF);

	printf("c2=%lf\n", c2);
}

bool CalcNormalizedGofK(
		const Mat& D,
		const vector<Mat>& P,
		const GlobalParam& param,
		const Mat& K0,
		Mat& K)
{
	Mat s=Mat::zeros(D.rows, D.cols, CV_64F);
	for (int i=0; i<P.size(); i++) {
		s+=P[i]*K0*P[i].t()+P[i].t()*K0*P[i];
	}
	s=(param.alpha*s-D)/param.beta;

	exp(s, K);
	double sums=sum(abs(K)).val[0];
	K/=sums;

	double error=norm(K-K0, NORM_INF);
	Log("Error=%lf\n", error);
	if (error<param.iter_threshold)
		return true;
	else
		return false;
}

bool SolveForSimilarity(
		const Mat& D,
		const vector<Mat>& P,
		const GlobalParam& param,
		Mat& K)
{
	Mat K1, K2;
	exp(D/(-param.beta), K1);
	double s=sum(abs(K1)).val[0];
	K1/=s;
	bool solved=false;
//	K.create(D.rows, D.cols, CV_64F);

	for (int t=0; t<param.max_iter/2; t++) {
		if (CalcNormalizedGofK(D, P, param, K1, K2)) {
			K2.copyTo(K);
			solved=true;
			break;
		}
		if (CalcNormalizedGofK(D, P, param, K2, K1)) {
			K1.copyTo(K);
			solved=true;
			break;
		}
	}

	if (!solved)
		K1.copyTo(K);
	return solved;
}

int LogoDetection(
		const Mat& K,
		const vector<ShapeDescriptor>& descriptor,
		const GlobalParam& param
		)
{

	int train_desc_size;
	int max_object_count=0;
	for (train_desc_size=0; train_desc_size<descriptor.size(); train_desc_size++) {
		if (descriptor[train_desc_size].object<0)
			break;
		if (descriptor[train_desc_size].object>max_object_count)
			max_object_count=descriptor[train_desc_size].object;
	}
	int test_desc_size=descriptor.size()-train_desc_size;

	int correct_matches[max_object_count+1];
	int all_matches[max_object_count+1];
	memset(correct_matches, 0, sizeof(int)*(max_object_count+1));
	memset(all_matches, 0, sizeof(int)*(max_object_count+1));

	for (int train=0; train<train_desc_size; train++) {
		double sumK=0;
		const double* testK=K.ptr<double>(train)+train_desc_size;
//		const double* testK=K.ptr<double>(train);
//		testK+=train_desc_size;

		for (int test=0; test<test_desc_size; test++)
			sumK+=testK[test];
		if (sumK==0) {
			Log("sumK=0 happened at %d\n", train);
			sumK=1;
		}
//		CV_Assert(sumK>0);

		all_matches[descriptor[train].object]++;
		double debug_sum_prob=0;
		for (int test=0; test<test_desc_size; test++) {
			double prob=testK[test]/sumK;
			debug_sum_prob+=prob;
//			Log("[%d/%d] prob=%lf\n", train, test, prob);
			if (prob>0.5) {
				// a very good match
				correct_matches[descriptor[train].object]++;
			}
		}
	}

	double match_ratio[max_object_count+1];
	int max_match=0;
	for (int i=0; i<=max_object_count; i++) {
		if (all_matches[i]==0)
			match_ratio[i]=0;
		else
			match_ratio[i]=static_cast<double>(correct_matches[i])/static_cast<double>(all_matches[i]);
		if (match_ratio[i]>match_ratio[max_match])
			max_match=i;
		Log("Match ratio %d = %lf\n", i, match_ratio[i]);
	}

	Log("Max match=%d, ratio=%lf\n", max_match, match_ratio[max_match]);

	if (match_ratio[max_match]<param.min_match_ratio)
		return -1;
	else
		return max_match;
}

void printHelp(const char* progname)
{
	printf("\n\n\n");
	printf("Usage: %s [generate/match] [options]\n", progname);
	printf("\n");
	printf("For generating descriptors: \n");
	printf("\tUsage: %s generate <image folder>\n", progname);
	printf("\n");
	printf("For matching images: \n");
	printf("\tUsage: %s match <image folder> <input image>", progname);
	printf("\n\n\n");
}


int main(int argc, const char* argv[])
{
	if (argc<2) {
		printHelp(argv[0]);
		exit(1);
	}

	if (!strcmp(argv[1], "generate")) {
		// generating image descriptors

		if (argc!=3) {
			printHelp(argv[0]);
			exit(1);
		}

		string dirname=string(argv[2]);
		if (dirname[dirname.size()-1]!='/')
			dirname=dirname+"/";

		// iterate through every file under one directory
		DIR* dp;
		dp=opendir(argv[2]);
		if (!dp) {
			fprintf(stderr, "Error open dir %s\n", argv[3]);
			exit(1);
		}

		dirent* dirp;

		vector<ShapeDescriptor> descriptors;
		vector<string> filemapping;

		while (NULL != (dirp=readdir(dp))) {
			string filepath=dirname+dirp->d_name;
			if (filepath.rfind("jpg")!=filepath.size()-3) continue;	// check if filename ends with xml

			Mat logoimg=imread(filepath);
			AddShapeDescriptor(logoimg, filemapping.size(), descriptors);	// adding logo sets
			filemapping.push_back(filepath);
			printf("/* Added file: %s. */\n", filepath.c_str());
		}

		// save descriptor and filemapping
		string filepath=dirname+"descriptor.data";
		FILE* fp=fopen(filepath.c_str(), "wb");
		uint32_t d_count=descriptors.size();
		fwrite(&d_count, sizeof(uint32_t), 1, fp);
		for (int i=0; i<d_count; i++)
			fwrite(&(descriptors[i]), sizeof(ShapeDescriptor), 1, fp);
		uint32_t f_count=filemapping.size();
		fwrite(&f_count, sizeof(uint32_t), 1, fp);
		for (int i=0; i<f_count; i++) {
			uint32_t filenamelen=filemapping[i].size()+1;
			fwrite(&filenamelen, sizeof(uint32_t), 1, fp);
			fwrite(filemapping[i].c_str(), sizeof(char), filenamelen, fp);
		}
		fclose(fp);

	} else if(!strcmp(argv[1], "match")) {
		// matching images
		if (argc!=4) {
			printHelp(argv[0]);
			exit(1);
		}

		// read descriptor files
		string dirname=string(argv[2]);
		if (dirname[dirname.size()-1]!='/')
			dirname=dirname+"/";

		string filepath;
		filepath=dirname+"descriptor.data";
		FILE* fp=fopen(filepath.c_str(), "rb");
		uint32_t d_count;
		int retval;
		retval=fread(&d_count, sizeof(uint32_t), 1, fp);
		assert(retval==1);
		vector<ShapeDescriptor> descriptors;
		descriptors.resize(d_count);
		for (int i=0; i<d_count; i++) {
			retval=fread(&(descriptors[i]), sizeof(ShapeDescriptor), 1, fp);
			assert(retval==1);
		}

		uint32_t f_count;
		retval=fread(&f_count, sizeof(uint32_t), 1, fp);
		assert(retval==1);
		vector<string> filemapping;
		filemapping.resize(f_count);
		for (int i=0; i<f_count; i++) {
			uint32_t filenamelen;
			retval=fread(&filenamelen, sizeof(uint32_t), 1, fp);
			assert(retval==1);
			char* tempst=(char*)malloc(sizeof(char)*filenamelen);
			retval=fread(tempst, sizeof(char), filenamelen, fp);
			assert(retval==filenamelen);
			filemapping[i]=tempst;
			free(tempst);
		}
		fclose(fp);

		// read testing image
		filepath=string(argv[3]);
		Mat logoimg=imread(filepath);
		AddShapeDescriptor(logoimg, -1, descriptors);

		// calculating
		Mat D;
		ConstructMatrixDistance(descriptors, D);
		// calculate max
		Mat Dsub(D, Range(0, d_count), Range(0, d_count));
		double max_val;
		minMaxLoc(Dsub, NULL, &max_val);
		printf("/* max value=%lf */\n", max_val);

		GlobalParam param;
		param.Na=8;
		param.Nr=8;
		param.epsilon_p=max_val;
		param.alpha=0.1;
		param.beta=0.1;
		param.max_iter=-2;
		param.iter_threshold=1e-6;
		param.min_match_ratio=0.4;

		vector<Mat> P;
		ConstructMatrixAdjacency(descriptors, D, param, P);
		Mat sum_P;
		sum_P.create(P[0].rows, P[0].cols, CV_64F);
		for (int i=0; i<P.size(); i++) {
			sum_P+=P[i];
		}

		Mat K;
		SolveForSimilarity(D, P, param, K);

		int matched=LogoDetection(K, descriptors, param);

		if (matched==-1)
			printf("Nothing matched.\n");
		else
			printf("%s\n", filemapping[matched].c_str());
	} else {
		printHelp(argv[0]);
		exit(1);
	}

	return 0;
}

int test_main()
{
	const string logoimg_filename="/home/bobbyzhu/aris/logo/imgres.jpg";
	Mat logoimg=imread(logoimg_filename);
	const string logoimg_filename2="/home/bobbyzhu/aris/logo/imgres-1.jpg";
	Mat logoimg2=imread(logoimg_filename2);
	const string logoimg_filename3="/home/bobbyzhu/aris/logo/imgres-2.jpg";
	Mat logoimg3=imread(logoimg_filename3);

//	const string testimg_filename="/home/bobbyzhu/aris/logo/imovie_youtube.jpg";
	const string testimg_filename="/home/bobbyzhu/aris/logo/photo6.jpg";
	Mat testimg=imread(testimg_filename);

	vector<ShapeDescriptor> descriptors;
	AddShapeDescriptor(logoimg, 0, descriptors);	// adding logo sets
	AddShapeDescriptor(logoimg2, 1, descriptors);
	AddShapeDescriptor(logoimg3, 2, descriptors);

	int train_pt_count=descriptors.size();

	AddShapeDescriptor(testimg, -1, descriptors);	// adding test image

	Mat D;
	ConstructMatrixDistance(descriptors, D);
	ofstream ofs("D.txt");
	ofs << D;
	ofs.close();

	// calculate max
	Mat Dsub(D, Range(0, train_pt_count), Range(0, train_pt_count));
	double max_val;
	minMaxLoc(Dsub, NULL, &max_val);
	Log("max value=%lf\n", max_val);

	GlobalParam param;
	param.Na=8;
	param.Nr=8;
	param.epsilon_p=max_val;
	param.alpha=0.1;
	param.beta=0.1;
	param.max_iter=-2;
	param.iter_threshold=1e-6;
	param.min_match_ratio=0.4;


	vector<Mat> P;
	ConstructMatrixAdjacency(descriptors, D, param, P);
	Mat sum_P;
	sum_P.create(P[0].rows, P[0].cols, CV_64F);
	for (int i=0; i<P.size(); i++) {
		sum_P+=P[i];
	}
	ofs.open("P.txt");
	ofs << sum_P;
	ofs.close();

	Mat K;
	SolveForSimilarity(D, P, param, K);
	ofs.open("K.txt");
	ofs << K;
	ofs.close();

	LogoDetection(K, descriptors, param);

	return 0;
}
